<?php

namespace App\Console\Commands;

use App\Enums\Severity;
use App\Filament\Resources\DocumentSchedules\DocumentScheduleResource;
use App\Models\DocumentSchedule;
use App\Services\CheckStatus;

class CheckExpiredDocumentsCommand extends StatusCheckCommand
{
    protected $signature = 'documents:check-expired {--no-sync : Non ricalcolare lo scadenziario prima del controllo}';

    protected $description = 'Stato dei documenti scaduti dello Scadenziario: il valore è l\'elenco; severity regular se scaduti da oltre 7 giorni, warning oltre 15, alert oltre 30';

    private const MAX_LISTED = 100;

    public function checkStatus(): CheckStatus
    {
        // Stessa tabella mostrata da DocumentScheduleResource: la si rinfresca come fa il pulsante
        // "Aggiorna scadenziario", altrimenti rifletterebbe l'ultimo ricalcolo manuale.
        if (! $this->option('no-sync')) {
            DocumentScheduleResource::syncScheduleTable();
        }

        $expired = DocumentSchedule::query()
            ->whereDate('expires_at', '<', now()->toDateString())
            ->orderBy('expires_at')
            ->get();

        if ($expired->isEmpty()) {
            return new CheckStatus('', Severity::Ok, 'Nessun documento scaduto.');
        }

        $daysOverdue = fn (DocumentSchedule $schedule): int => (int) $schedule->expires_at->startOfDay()->diffInDays(now()->startOfDay());

        $oldestDays = $daysOverdue($expired->first());

        $list = $expired->take(self::MAX_LISTED)->map(fn (DocumentSchedule $schedule): string => sprintf(
            '- %s — %s (%s): scaduto il %s (%d giorni fa)',
            $schedule->entity_name,
            $schedule->document_name,
            $schedule->document_type_name,
            $schedule->expires_at->format('d/m/Y'),
            $daysOverdue($schedule),
        ))->implode("\n");

        if ($expired->count() > self::MAX_LISTED) {
            $list .= "\n... e altri ".($expired->count() - self::MAX_LISTED).' documenti.';
        }

        return new CheckStatus(
            $list,
            $this->severityFor($oldestDays),
            "{$expired->count()} documenti scaduti, il più vecchio da {$oldestDays} giorni.",
        );
    }

    /**
     * Il grado dipende da quanto è vecchia la scadenza più in ritardo.
     */
    private function severityFor(int $oldestOverdueDays): Severity
    {
        return match (true) {
            $oldestOverdueDays > 30 => Severity::Alert,
            $oldestOverdueDays > 15 => Severity::Warning,
            $oldestOverdueDays > 7 => Severity::Regular,
            default => Severity::Ok,
        };
    }
}
