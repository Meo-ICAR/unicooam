<?php

namespace App\ValueObjects;

use Carbon\Carbon;

class OamSemester
{
    public readonly Carbon $start;

    public readonly Carbon $end;

    public function __construct(int $year, int $semesterNumber)
    {
        if ($semesterNumber === 1) {
            $this->start = Carbon::create($year, 1, 1, 0, 0, 0);
            $this->end = Carbon::create($year, 6, 30, 23, 59, 59);
        } else {
            $this->start = Carbon::create($year, 7, 1, 0, 0, 0);
            $this->end = Carbon::create($year, 12, 31, 23, 59, 59);
        }
    }

    /**
     * Calcola il semestre di riferimento in base alla data odierna
     * Finestra 1: Fino a Ottobre -> Mostra il 1° Semestre dell'anno corrente
     * Finestra 2: Da Novembre ad Aprile -> Mostra il 2° Semestre dell'anno precedente/corrente
     */
    public static function getInBaseAlMeseCorrente(): self
    {
        $oggi = Carbon::now();
        $mese = $oggi->month;
        $anno = $oggi->year;

        // Se siamo tra Gennaio (1) e Ottobre (10) -> Riferimento: 1° Semestre anno corrente
        if ($mese <= 10) {
            return new self($anno, 1);
        }

        // Se siamo a Novembre (11) o Dicembre (12) -> Riferimento: 2° Semestre anno corrente
        // Se siamo tra Gennaio e Aprile dell'anno dopo (gestito implicitamente dal ciclo dell'applicazione)
        return new self($anno, 2);
    }
}
