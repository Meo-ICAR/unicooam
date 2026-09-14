<?php

namespace App\Filament\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OamCompletoExport implements WithMultipleSheets
{
    protected array $datiAnagrafica;

    protected array $datiEconomici;

    protected array $datiEconomiciBase;

    protected array $datiInformativo;

    protected array $datiSedi;

    protected array $datiPrudenziale;

    public function __construct(
        array $datiAnagrafica,
        array $datiEconomici,
        array $datiInformativo,
        array $datiSedi,
        array $datiPrudenziale,
        array $datiEconomiciBase
    ) {
        $this->datiAnagrafica = $datiAnagrafica;
        $this->datiEconomici = $datiEconomici;
        $this->datiInformativo = $datiInformativo;
        $this->datiSedi = $datiSedi;
        $this->datiPrudenziale = $datiPrudenziale;
        $this->datiEconomiciBase = $datiEconomiciBase;
    }

    /**
     * Ritorna l'array di tutti i fogli che comporranno il file Excel
     */
    public function sheets(): array
    {
        return [
            new M510AnagraficaSheet($this->datiAnagrafica),
            new M510EconomicoOldSheet($this->datiEconomiciBase),
            new M510EconomicoBaseSheet($this->datiEconomici),
            new M510InformativoSheet($this->datiInformativo),
            new M510SediSheet($this->datiSedi),
            new M510PrudenzialeSheet($this->datiPrudenziale),
        ];
    }
}
