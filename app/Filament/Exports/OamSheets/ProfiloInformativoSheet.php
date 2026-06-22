<?php

namespace App\Filament\Exports\OamSheets;

use App\Models\Company;
use App\Models\Website;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProfiloInformativoSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly Company $company,
        private readonly string $periodoLabel,
    ) {}

    public function title(): string
    {
        return 'PROFILO INFORMATIVO';
    }

    public function array(): array
    {
        $websites = Website::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->orderByDesc('is_typical')
            ->get();

        $nSitiWeb = $websites->where('is_typical', true)->count();

        $rows = [
            ['SEZIONE D — PROFILO INFORMATIVO E DI TRASPARENZA'],
            ['Periodo: '.$this->periodoLabel],
            [],
            ['Codice', 'Denominazione campo', 'Valore'],
            ['MPI1', 'Numero siti web aziendali attivi', $nSitiWeb],
            [],
            ['ELENCO SITI WEB'],
            [],
            ['MPI', 'Dominio', 'Data aggiorn. Trasparenza', 'Data aggiorn. Privacy', 'Footer Compliant', 'ISO 27001'],
        ];

        foreach ($websites->take(7) as $i => $site) {
            $rows[] = [
                'MPI'.($i + 2),
                $site->domain ?? '—',
                $site->transparency_date?->format('d/m/Y') ?? '—',
                $site->privacy_date?->format('d/m/Y') ?? '—',
                $site->is_footercompilant ? 'SÌ' : 'NO',
                $site->is_iso27001_certified ? 'SÌ' : 'NO',
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

                $sheet->getStyle('A4:D4')->getFont()->setBold(true);
                $sheet->getStyle('A4:D4')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');

                $sheet->getStyle('A9:F9')->getFont()->setBold(true);
                $sheet->getStyle('A9:F9')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
            },
        ];
    }
}
