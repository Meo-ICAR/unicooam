<?php

namespace App\ValueObjects;

use Carbon\CarbonImmutable;
use Unico\Core\ValueObjects\OamSemester as CoreOamSemester;

/** @deprecated Preferire Unico\Core\ValueObjects\OamSemester */
class OamSemester extends CoreOamSemester
{
    /**
     * Semestre della segnalazione in corso: da gennaio a febbraio è l'ultimo semestre dell'anno precedente,
     * fino a ottobre il primo semestre dell'anno, da novembre il secondo.
     */
    public static function current(?CarbonImmutable $now = null): static
    {
        $now ??= CarbonImmutable::now();

        if ($now->month <= 2) {
            return new static($now->year - 1, 2);
        }

        return parent::current($now);
    }

    /**
     * Semestri selezionabili nei filtri (periodo => etichetta).
     *
     * @return array<string, string>
     */
    public static function filterOptions(): array
    {
        return [
            '202606' => '2026 - Giugno',
            '202612' => '2026 - Dicembre',
            '202706' => '2027 - Giugno',
        ];
    }
}
