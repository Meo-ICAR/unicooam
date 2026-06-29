<?php

namespace App\Filament\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class M510Export implements WithMultipleSheets
{
    protected array $dati;

    public function __construct(array $dati)
    {
        $this->dati = $dati;
    }

    public function sheets(): array
    {
        return [
            new M510AnagraficaSheet($this->dati['anagrafica']),
            new M510PrudenzialeSheet($this->dati['prudenziale']),
        ];
    }
}
