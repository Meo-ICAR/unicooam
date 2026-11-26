<?php

namespace App\Console\Commands;

use App\Enums\AuditStatus;
use App\Models\Audit;
use App\Models\Fornitore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Unico\Core\Documents\MarkitdownConverter;

/**
 * Audit interno per ogni produttore indicato, nato da un'email di UnicoPEC (verifiche ispettive periodiche): il testo standard (Word) diventa il corpo
 * dell'email e l'ambito dell'audit, gli altri allegati (questionario PDF, checklist Excel) viaggiano con l'email. È sicuro rilanciarlo: per ogni coppia
 * email e produttore c'è un solo audit, e la comunicazione si rimanda solo se non risulta ancora inviata.
 *
 * Il testo standard è scritto per un nominativo di esempio («Egr. sig. Graziani Pellegrino»): per ogni produttore quel nominativo è sostituito con il suo,
 * e la riga «Oggetto: ...» del documento diventa l'oggetto dell'email.
 */
class OpenAuditsFromEmailCommand extends Command
{
    protected $signature = 'audits:open-from-email
        {--email= : ID dell\'email in UnicoPEC}
        {--produttori= : ID dei produttori, separati da virgola}
        {--dry-run : Mostra cosa farebbe senza creare audit né inviare email}';

    protected $description = 'Apre un audit interno per ciascun produttore indicato, nato da un\'email di UnicoPEC, e gli invia il testo standard con gli allegati';

    private const TEXT_EXTENSIONS = ['docx', 'doc', 'odt', 'rtf'];

    public function handle(): int
    {
        $emailId = (int) $this->option('email');
        $ids = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $this->option('produttori'))), fn ($id) => ctype_digit($id))));

        if ($emailId <= 0 || $ids === []) {
            $this->error('Servono --email e --produttori (id separati da virgola).');

            return self::INVALID;
        }

        try {
            $email = $this->fetchEmail($emailId);
            [$text, $files] = $this->content($email);
        } catch (\Throwable $e) {
            $this->error("Email #{$emailId}: ".$e->getMessage());
            Log::error('audits:open-from-email: email non leggibile', ['email_id' => $emailId, 'error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $failed = false;

        foreach ($ids as $id) {
            $producer = Fornitore::query()->find($id);

            if (! $producer) {
                $this->warn("Produttore #{$id}: non trovato.");
                $failed = true;

                continue;
            }

            try {
                $this->open($email, $producer, $text, $files);
            } catch (\Throwable $e) {
                $failed = true;
                $this->error("{$producer->name}: ".$e->getMessage());
                Log::error('audits:open-from-email: produttore non completato', ['email_id' => $emailId, 'fornitore_id' => $producer->getKey(), 'error' => $e->getMessage()]);
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function fetchEmail(int $id): array
    {
        return $this->api()->get("/api/incoming-emails/{$id}")->throw()->json();
    }

    private function api(): \Illuminate\Http\Client\PendingRequest
    {
        $url = rtrim((string) config('services.unicopec.url'), '/');

        if ($url === '' || blank(config('services.unicopec.key'))) {
            throw new RuntimeException('UNICOPEC_API_URL o la chiave del bridge non configurati.');
        }

        return Http::baseUrl($url)->withHeaders(['X-Api-Key' => (string) config('services.unicopec.key')])->acceptJson()->timeout(60);
    }

    /**
     * Testo standard (primo allegato Word) e allegati da inoltrare (tutti gli altri, scaricati da UnicoPEC).
     *
     * @param  array<string, mixed>  $email
     * @return array{0: string, 1: array<int, array{name: string, mime: string, bytes: string}>}
     */
    private function content(array $email): array
    {
        $text = null;
        $files = [];

        foreach ((array) ($email['attachments'] ?? []) as $attachment) {
            $name = (string) $attachment['name'];
            $bytes = $this->api()->get("/api/incoming-emails/{$email['id']}/attachments/{$attachment['id']}")->throw()->body();

            if ($text === null && in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::TEXT_EXTENSIONS, true)) {
                $text = MarkitdownConverter::make()->toMarkdown($bytes, $name);

                continue;
            }

            $files[] = ['name' => $name, 'mime' => (string) $attachment['mime'], 'bytes' => $bytes];
        }

        if (blank($text)) {
            throw new RuntimeException('manca il testo standard (allegato Word) o non è leggibile.');
        }

        return [trim($text), $files];
    }

    /**
     * @param  array<string, mixed>  $email
     * @param  array<int, array{name: string, mime: string, bytes: string}>  $files
     */
    private function open(array $email, Fornitore $producer, string $text, array $files): void
    {
        [$subject, $text] = self::personalize($text, self::displayName((string) $producer->name), (string) ($email['subject'] ?? 'Verifica ispettiva'));

        if (blank($producer->email)) {
            throw new RuntimeException('il produttore non ha un indirizzo email.');
        }

        if ($this->option('dry-run')) {
            $this->line("[prova] {$producer->name} <{$producer->email}>: oggetto «{$subject}», ".count($files).' allegati.');

            return;
        }

        $audit = Audit::withoutGlobalScopes()->firstOrNew([
            'source_email_id' => $email['id'],
            'auditable_type' => 'fornitore',
            'auditable_id' => (string) $producer->getKey(),
        ]);

        if (! $audit->exists) {
            $audit->forceFill([
                'company_id' => $producer->company_id,
                'name' => mb_substr($subject, 0, 250),
                'auditor_name' => $email['from_name'] ?: $email['from_email'],
                'scheduled_at' => now()->toDateString(),
                'status' => AuditStatus::PLANNED,
                'origin_type' => 'internal',
                'execution_method' => 'intervista',
                'scope' => $text,
            ])->save();
        }

        if ($audit->notified_at) {
            $this->line("{$producer->name}: audit #{$audit->id} già comunicato.");

            return;
        }

        Mail::raw($text, function ($message) use ($producer, $subject, $files) {
            $message->to($producer->email, $producer->name)->subject($subject);

            foreach ($files as $file) {
                $message->attachData($file['bytes'], $file['name'], ['mime' => $file['mime']]);
            }
        });

        $audit->forceFill(['notified_at' => now()])->save();
        $this->info("{$producer->name}: audit #{$audit->id} aperto e comunicato a {$producer->email}.");
    }

    /**
     * Testo e oggetto per un produttore: la riga «Oggetto: ...» (se c'è) diventa l'oggetto e il nominativo di esempio, riconosciuto dalla riga
     * «Egr. sig. NOME», è sostituito con quello del produttore. Se il documento non ha un nominativo da sostituire resta com'è.
     *
     * @return array{0: string, 1: string} oggetto, testo
     */
    public static function personalize(string $text, string $name, string $defaultSubject): array
    {
        $text = trim(str_replace('**', '', $text));
        $example = preg_match('/^\s*(?:Egr|Gent)[^\n]*?\bsig\.?(?:ra)?\s+([^\n]+?)\s*$/imu', $text, $m) ? trim($m[1], " .,") : null;

        if ($example !== null && $example !== '') {
            $text = str_ireplace($example, $name, $text);
        }

        $subject = $defaultSubject;

        if (preg_match('/\A\s*Oggetto:\s*([^\n]+)\n/iu', $text, $m)) {
            $subject = trim($m[1]) ?: $defaultSubject;
            $text = trim(substr($text, strlen($m[0])));
        }

        return [mb_substr($subject, 0, 250), $text];
    }

    /** «GIOFRE' ALFONSO» → «Giofrè Alfonso»: iniziali maiuscole (anche dopo apostrofo e trattino) e, a fine parola, l'apostrofo al posto dell'accento (non in «D'Angelo»). */
    public static function displayName(string $name): string
    {
        $accents = ['a' => 'à', 'e' => 'è', 'i' => 'ì', 'o' => 'ò', 'u' => 'ù'];
        $title = preg_replace_callback("/(^|[\\s'\\-])(\\p{L})/u", fn ($m) => $m[1].mb_strtoupper($m[2]), mb_strtolower(trim($name)));

        return preg_replace_callback("/([aeiou])'(?=\\s|$)/u", fn ($m) => $accents[$m[1]], $title);
    }
}
