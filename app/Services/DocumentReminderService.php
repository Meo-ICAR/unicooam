<?php

namespace App\Services;

use Unico\Core\Enums\DocumentStatus;
use App\Enums\Severity;
use App\Mail\DocumentReminderMail;
use App\Models\Document;
use App\Models\DocumentReminder;
use App\Models\EmailTemplate;
use App\Support\DocumentRecipientResolver;
use App\Support\EmailTemplateRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DocumentReminderService
{
    public function __construct(
        protected DocumentRecipientResolver $recipientResolver,
        protected EmailTemplateRenderer $templateRenderer,
    ) {}

    public function scheduleQuery(): Builder
    {
        $windowDays = (int) config('documents.schedule_window_days', 90);
        $until = now()->addDays($windowDays)->toDateString();

        $query = Document::query()
            ->with(['documentType', 'documentable'])
            ->where(function (Builder $query) use ($until) {
                // Opzione 1: Tutte le tue regole attuali raggruppate
                $query->where(function (Builder $subQuery) use ($until) {
                    $subQuery->where('is_monitored', true)
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', $until)
                        ->whereNotIn('status', [
                            DocumentStatus::REJECTED->value,
                            DocumentStatus::NA->value,
                        ])
                        ->where(function (Builder $reminderQuery) {
                            $reminderQuery->whereNull('last_sent_at')
                                ->orWhere('last_sent_at', '<', now()->subDays(5));
                        });
                })
                // Opzione 2: OPPURE qualsiasi documento che sia semplicemente PENDING
                    ->orWhere('status', DocumentStatus::PENDING->value);
            });

        $this->excludeSupersededVersions($query);

        return $query->orderBy('expires_at');
    }

    /**
     * Esclude le vecchie versioni di un documento: quelle rinnovate (metadata.renewed_to_uuid,
     * status "expired" impostato da Document::renew()) e quelle per cui esiste un documento più
     * recente dello stesso tipo sullo stesso destinatario (a pari emissione, con scadenza più
     * lontana) e quelle rinnovate da un altro tipo di documento (renewed_by_id). Una versione più recente respinta,
     * illeggibile o N/A non sostituisce la precedente.
     *
     * @param  Builder<Document>  $query
     */
    private function excludeSupersededVersions(Builder $query): void
    {
        $query
            ->whereNull('documents.metadata->renewed_to_uuid')
            ->where('documents.status', '!=', DocumentStatus::EXPIRED->value)
            ->whereNotExists(function ($newer): void {
                $newer->selectRaw('1')
                    ->from('documents as newer')
                    ->whereColumn('newer.documentable_type', 'documents.documentable_type')
                    ->whereColumn('newer.documentable_id', 'documents.documentable_id')
                    ->whereColumn('newer.document_type_id', 'documents.document_type_id')
                    ->whereColumn('newer.id', '!=', 'documents.id')
                    ->whereNull('newer.deleted_at')
                    ->whereNotNull('newer.emitted_at')
                    ->whereNotNull('documents.emitted_at')
                    ->where(function ($later): void {
                        $later->whereColumn('newer.emitted_at', '>', 'documents.emitted_at')
                            // A pari data di emissione prevale la scadenza più lontana.
                            ->orWhere(function ($tie): void {
                                $tie->whereColumn('newer.emitted_at', '=', 'documents.emitted_at')
                                    ->whereNotNull('newer.expires_at')
                                    ->whereNotNull('documents.expires_at')
                                    ->whereColumn('newer.expires_at', '>', 'documents.expires_at');
                            });
                    })
                    ->whereNotIn('newer.status', [
                        DocumentStatus::REJECTED->value,
                        DocumentStatus::UNREADABLE->value,
                        DocumentStatus::NA->value,
                    ]);
            })
            // Tipo rinnovabile da un altro tipo (document_types.renewed_by_id): un documento del
            // tipo rinnovatore, emesso dalla stessa data in poi, sostituisce quello vecchio.
            ->whereNotExists(function ($renewer): void {
                $renewer->selectRaw('1')
                    ->from('documents as renewer')
                    ->whereRaw('renewer.document_type_id = (select dt.renewed_by_id from document_types as dt where dt.id = documents.document_type_id)')
                    ->whereColumn('renewer.documentable_type', 'documents.documentable_type')
                    ->whereColumn('renewer.documentable_id', 'documents.documentable_id')
                    ->whereNull('renewer.deleted_at')
                    ->whereNotNull('renewer.emitted_at')
                    ->whereNotNull('documents.emitted_at')
                    ->whereColumn('renewer.emitted_at', '>=', 'documents.emitted_at')
                    ->whereNotIn('renewer.status', [
                        DocumentStatus::REJECTED->value,
                        DocumentStatus::UNREADABLE->value,
                        DocumentStatus::NA->value,
                    ]);
            });
    }

    /**
     * Stato dello scadenziario per UnicoBPM: numero di documenti monitorati scaduti o in scadenza
     * entro 30 giorni. Il grado dipende dal più urgente: scaduto = alert, entro 7 giorni = warning,
     * entro 30 = regular. Non invia nulla.
     */
    public function expiryStatus(int $windowDays = 30): CheckStatus
    {
        $documents = Document::query()
            ->with('documentType')
            ->where('is_monitored', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($windowDays)->toDateString())
            ->whereNotIn('status', [DocumentStatus::REJECTED->value, DocumentStatus::NA->value])
            ->orderBy('expires_at')
            ->get();

        if ($documents->isEmpty()) {
            return new CheckStatus(0, Severity::Ok);
        }

        $mostUrgentDays = $this->daysUntilExpiry($documents->first());

        $severity = match (true) {
            $mostUrgentDays < 0 => Severity::Alert,
            $mostUrgentDays <= 7 => Severity::Warning,
            default => Severity::Regular,
        };

        $details = $documents->take(20)->map(fn (Document $document): string => sprintf(
            '- %s (%s) — %s',
            $document->name,
            $document->documentType?->name ?? 'Documento',
            $this->daysUntilExpiry($document) < 0 ? 'SCADUTO il ' : 'scade il '
        ).$document->expires_at->format('d/m/Y'))->implode("\n");

        if ($documents->count() > 20) {
            $details .= "\n... e altri ".($documents->count() - 20).' documenti.';
        }

        return new CheckStatus($documents->count(), $severity, $details);
    }

    /**
     * @return Collection<int, Collection<int, Document>>
     */
    public function groupedDocuments(?Builder $query = null): Collection
    {
        $documents = ($query ?? $this->scheduleQuery())->get();

        return $documents->groupBy(
            fn (Document $document): string => $document->documentable_type.'|'.$document->documentable_id
        );
    }

    /**
     * @return array{sent: int, skipped: int, failed: int, groups: int}
     */
    public function sendReminders(bool $onlyDueToday = true, ?string $groupKey = null): array
    {
        $template = EmailTemplate::query()
            ->where('code', 'DOC_EXPIRING')
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return ['sent' => 0, 'skipped' => 0, 'failed' => 0, 'groups' => 0];
        }

        $stats = ['sent' => 0, 'skipped' => 0, 'failed' => 0, 'groups' => 0];

        $groups = $this->groupedDocuments();

        if ($groupKey !== null) {
            $groups = $groups->only([$groupKey]);
        }

        foreach ($groups as $documents) {
            $dueDocuments = $documents
                ->filter(fn (Document $document): bool => $this->shouldRemind($document, $onlyDueToday))
                ->values();

            if ($dueDocuments->isEmpty()) {
                $stats['skipped'] += $documents->count();

                continue;
            }

            $stats['groups']++;

            $recipient = $this->recipientResolver->resolveForDocument($dueDocuments->first());

            if (blank($recipient['email'])) {
                $stats['skipped'] += $dueDocuments->count();

                continue;
            }

            try {
                $rendered = $this->renderGroupEmail($template, $recipient['name'], $dueDocuments);

                Mail::to($recipient['email'])->send(
                    new DocumentReminderMail($rendered['subject'], $rendered['body'])
                );

                foreach ($dueDocuments as $document) {
                    $this->recordReminder($document, $this->daysUntilExpiry($document), $recipient['email']);
                    $stats['sent']++;
                }
            } catch (\Throwable $exception) {
                foreach ($dueDocuments as $document) {
                    $this->recordFailedReminder(
                        $document,
                        $this->daysUntilExpiry($document),
                        $recipient['email'],
                        $exception->getMessage()
                    );
                    $stats['failed']++;
                }
            }
        }

        return $stats;
    }

    public function shouldRemind(Document $document, bool $onlyDueToday = true): bool
    {
        $daysUntilExpiry = $this->daysUntilExpiry($document);

        if ($onlyDueToday) {
            if (! in_array($daysUntilExpiry, $this->notifyThresholds($document), true)) {
                return false;
            }

            return ! $this->reminderAlreadySent($document, $daysBefore = $daysUntilExpiry);
        }

        return true;
    }

    public function daysUntilExpiry(Document $document): int
    {
        return (int) now()->startOfDay()->diffInDays($document->expires_at?->startOfDay(), false);
    }

    /**
     * @return array<int>
     */
    public function notifyThresholds(Document $document): array
    {
        $configured = $document->documentType?->notify_days_before;

        if (is_array($configured) && $configured !== []) {
            return array_map('intval', $configured);
        }

        return array_map('intval', config('documents.default_notify_days_before', [30, 15, 7, 1, 0]));
    }

    /**
     * @param  Collection<int, Document>  $documents
     * @return array{subject: string, body: string}
     */
    protected function renderGroupEmail(EmailTemplate $template, string $recipientName, Collection $documents): array
    {
        $listItems = $documents
            ->map(function (Document $document): string {
                $status = $this->daysUntilExpiry($document) < 0 ? 'SCADUTO' : $document->expires_at?->format('d/m/Y');

                return sprintf(
                    '<li><strong>%s</strong> (%s) — scadenza: %s</li>',
                    e($document->name),
                    e($document->documentType?->name ?? 'Documento'),
                    e((string) $status)
                );
            })
            ->implode('');

        $firstDocument = $documents->first();

        $rendered = $this->templateRenderer->render($template, [
            '{agente_nome}' => $recipientName,
            '{documento_nome}' => $documents->count() === 1
                ? (string) $firstDocument?->name
                : $documents->count().' documenti',
            '{data_scadenza}' => $documents->count() === 1
                ? ($firstDocument?->expires_at?->format('d/m/Y') ?? '—')
                : 'vedi elenco',
        ]);

        if (! Str::contains($rendered['body'], '{elenco_documenti}')) {
            $rendered['body'] .= '<ul>'.$listItems.'</ul>';
        } else {
            $rendered['body'] = str_replace('{elenco_documenti}', '<ul>'.$listItems.'</ul>', $rendered['body']);
        }

        if ($documents->count() > 1) {
            $rendered['subject'] = 'Sollecito scadenze documenti ('.$documents->count().')';
        }

        return $rendered;
    }

    protected function reminderAlreadySent(Document $document, int $daysBefore): bool
    {
        return DocumentReminder::query()
            ->where('document_id', $document->id)
            ->where('days_before', $daysBefore)
            ->where('status', 'sent')
            ->exists();
    }

    protected function recordReminder(Document $document, int $daysBefore, string $email): void
    {
        // 1. Registra lo storico del reminder inviato
        DocumentReminder::query()->updateOrCreate(
            [
                'document_id' => $document->id,
                'days_before' => $daysBefore,
            ],
            [
                'recipient_email' => $email,
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => now(),
            ]
        );

        // 2. Aggiorna contatori e stato sul documento principale in una sola query atomica
        $document->update([
            'reminders_count' => $document->reminders_count + 1,
            'last_sent_at' => now(),
            'status' => DocumentStatus::PROVISIONAL->value, // Impostato a provvisorio/in attesa dopo il sollecito
        ]);
    }

    protected function recordFailedReminder(Document $document, int $daysBefore, string $email, string $message): void
    {
        DocumentReminder::query()->updateOrCreate(
            [
                'document_id' => $document->id,
                'days_before' => $daysBefore,
            ],
            [
                'recipient_email' => $email,
                'status' => 'failed',
                'error_message' => $message,
                'sent_at' => now(),
            ]
        );
    }
}
