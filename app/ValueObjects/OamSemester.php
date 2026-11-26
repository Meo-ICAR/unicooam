<?php

namespace App\ValueObjects;

use Carbon\CarbonImmutable;
use Unico\Core\ValueObjects\OamSemester as CoreOamSemester;

/** @deprecated Preferire Unico\Core\ValueObjects\OamSemester */
class OamSemester extends CoreOamSemester
{
    /**
     * Semestre della segnalazione in corso: da gennaio a febbraio è l'ultimo semestre dell'anno precedente,
     * fino al 14 novembre il primo semestre dell'anno, dal 15 novembre il secondo.
     */
    public static function current(?CarbonImmutable $now = null): static
    {
        $now ??= CarbonImmutable::now();

        if ($now->month <= 2) {
            return new static($now->year - 1, 2);
        }

        $isSecondSemester = $now->month > 11 || ($now->month === 11 && $now->day >= 15);

        return new static($now->year, $isSecondSemester ? 2 : 1);
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
