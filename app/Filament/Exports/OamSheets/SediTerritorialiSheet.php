<?php

namespace App\Filament\Exports\OamSheets;

use App\Models\Branch;
use App\Models\Company;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SediTerritorialiSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly Company $company,
        private readonly string $periodoLabel,
    ) {}

    public function title(): string
    {
        return 'ELENCO SEDI TERRITORIALI';
    }

    public function array(): array
    {
        $branches = Branch::where('company_id', $this->company->id)
            ->whereNull('dismissed_at')
            ->orderByDesc('is_main_office')
            ->orderBy('city')
            ->get();

        $rows = [
            ['SEZIONE E — ELENCO SEDI TERRITORIALI'],
            ['Periodo: '.$this->periodoLabel],
            [],
            [
                'N. Iscrizione OAM (M510)',
                'Indirizzo',
                'Numero Civico',
                'Città',
                'CAP',
                'Provincia',
                'Regione',
                'Cognome Responsabile',
                'Nome Responsabile',
                'Sede Principale (SI/NO)',
            ],
        ];

        foreach ($branches as $branch) {
            $rows[] = [
                $this->company->oam ?? '',
                $branch->address ?? '',
                $branch->street_number ?? '',
                $branch->city ?? '',
                $branch->zip_code ?? '',
                $branch->province ?? '',
                $branch->region ?? '',
                $branch->manager_last_name ?? '',
                $branch->manager_first_name ?? '',
                $branch->is_main_office ? 'SI' : 'NO',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestCol = $sheet->getHighestColumn();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A1:'.$highestCol.'1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF1F497D');
                $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF');

                $sheet->getStyle('A4:J4')->getFont()->setBold(true);
                $sheet->getStyle('A4:J4')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');

                // Evidenzia sedi principali
                $highestRow = $sheet->getHighestRow();
                for ($row = 5; $row <= $highestRow; $row++) {
                    if ($sheet->getCell('J'.$row)->getValue() === 'SI') {
                        $sheet->getStyle('A'.$row.':J'.$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFFFF2CC');
                    }
                }
            },
        ];
    }
}
