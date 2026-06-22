<?php

namespace App\Filament\Exports\OamSheets;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PROFORMA\Fornitore;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AnagraficaSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly Company $company,
        private readonly string $periodoLabel,
        private readonly string $year,
        private readonly int $semestre,
        private readonly ?int $numeroProgressivo = null,
    ) {}

    public function title(): string
    {
        return 'ANAGRAFICA';
    }

    public function array(): array
    {
        $dipendenti = Employee::where('company_id', $this->company->id)
            ->whereNull('termination_date')
            ->count();

        $collaboratori = 0;
        try {
            $collaboratori = Fornitore::where('is_dummy', false)->count();
        } catch (\Throwable) {
            // Database esterno non disponibile
        }
        $sedi = Branch::where('company_id', $this->company->id)->count();

        return [
            ['SEZIONE A — ANAGRAFICA'],
            [],
            ['Codice', 'Denominazione campo', 'Valore'],
            ['MA1', 'Denominazione / Ragione Sociale', $this->company->name],
            ['MA2', 'Codice Fiscale / Partita IVA', $this->company->vat_number],
            ['MA3', 'Periodo di rilevazione', $this->periodoLabel],
            ['MA4A', 'Numero dipendenti (al 31/'.($this->semestre === 1 ? '06' : '12').'/'.$this->year.')', $dipendenti],
            ['MA4B', 'Numero collaboratori / agenti in rete', $collaboratori],
            ['MA5', 'Numero sedi territoriali', $sedi],
            ['MA6', 'N. progressivo segnalazione', $this->numeroProgressivo ?? 1],
            ['', 'Numero iscrizione OAM (M510)', $this->company->oam ?? ''],
            ['', 'Numero iscrizione RUI IVASS', $this->company->numero_iscrizione_rui ?? ''],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                // Titolo
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1:C1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF1F497D');
                $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF');

                // Intestazioni colonne
                $sheet->getStyle('A3:C3')->getFont()->setBold(true);
                $sheet->getStyle('A3:C3')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');

                // Evidenziamo i valori
                $sheet->getStyle('C4:C15')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }
}
