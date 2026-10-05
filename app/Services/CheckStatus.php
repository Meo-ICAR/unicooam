<?php

namespace App\Services;

use App\Enums\Severity;

/**
 * Stato di un check restituito a UnicoBPM: il valore misurato, la severity e un dettaglio
 * leggibile opzionale. Cosa farne (email, destinatari) lo decide UnicoBPM tramite la RACI.
 */
readonly class CheckStatus
{
    public function __construct(
        public int|string $value,
        public Severity $severity,
        public ?string $details = null,
    ) {}

    /**
     * @return array{value: int|string, severity: string, details: ?string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'severity' => $this->severity->value,
            'details' => $this->details,
        ];
    }
}
