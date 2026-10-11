<?php

namespace App\Services;

use App\Enums\Severity;
use Unico\Core\Documents\Reminders\DocumentReminderService as CoreDocumentReminderService;

/**
 * Scadenziario e solleciti: la logica è del pacchetto (Unico\Core\Documents\Reminders). Qui resta solo la traduzione dello stato
 * dello scadenziario nel CheckStatus che unicoloan/unicobpm leggono via API; tutto il resto passa al servizio del pacchetto.
 *
 * @mixin CoreDocumentReminderService
 */
class DocumentReminderService
{
    public function __construct(private CoreDocumentReminderService $core) {}

    public function expiryStatus(int $windowDays = 30): CheckStatus
    {
        $status = $this->core->expiryStatus($windowDays);

        return new CheckStatus($status->count, Severity::from($status->severity), $status->details === '' ? null : $status->details);
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->core->{$method}(...$arguments);
    }
}
