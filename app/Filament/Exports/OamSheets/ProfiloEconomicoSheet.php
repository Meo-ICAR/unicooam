<?php

namespace App\Filament\Exports\OamSheets;

use App\Models\Company;
use App\Models\OamSemestrale;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProfiloEconomicoSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    /** Ordine righe OAM per prodotto creditizio */
    private const PRODOTTI_ORDER = [
        'Mutuo Ipotecario',
        'Cessione del Quinto',
        'TFS/TFR Liquidazione',
        'Credito al Consumo',
        'Segnalazione Mutuo',
    ];

    /** Etichette MPEB corrispondenti */
    private const MPEB_LABELS = [
        'Mutuo Ipotecario' => 'MPEB1',
        'Cessione del Quinto' => 'MPEB2',
        'TFS/TFR Liquidazione' => 'MPEB3',
        'Credito al Consumo' => 'MPEB4',
        'Segnalazione Mutuo' => 'MPEB5',
    ];

    public function __construct(
        private readonly Company $company,
        private readonly string $period,
        private readonly string $periodoLabel,
    ) {}

    public function title(): string
    {
        return 'PROFILO ECONOMICO BASE';
    }

    public function array(): array
    {
        $rows = OamSemestrale::where('company_id', $this->company->id)
            ->where('period', $this->period)
            ->get()
            ->keyBy('prodotto_creditizio');

        $headers = [
            ['SEZIONE B — PROFILO ECONOMICO / OPERATIVO BASE'],
            ['Periodo: '.$this->periodoLabel],
            [],
            [
                'Codice OAM',
                'Prodotto Creditizio',
                'N. Intermediari Conv.',
                'N. Intermediari Non Conv.',
                'N. Pratiche Intermediate',
                'N. Pratiche in Lavorazione',
                'Montante Lordo / Erogato (€)',
                'Valore Pratiche in Lavorazione (€)',
                'Provv. da Clientela (€)',
                'Provv. da Istituto Erogante (€)',
                'Premi da Istituto Erogante (€)',
                'PAY-IN ass. da Banche (€)',
                'PAY-IN ass. da Broker (€)',
                'PAY-IN ass. da Broker Captive (€)',
                'PAY-OUT Rete Credito (€)',
                'PAY-OUT Rete ass. Banche (€)',
                'PAY-OUT Rete ass. Broker (€)',
                'PAY-OUT Rete ass. Broker Captive (€)',
                'N. Rivalse art. 125-sexies TUB',
                'Importo Provvigioni Retrocesse (€)',
            ],
        ];

        $dataRows = [];
        $totals = array_fill(0, 18, 0);

        foreach (self::PRODOTTI_ORDER as $prodotto) {
            $row = $rows->get($prodotto);
            $mpeb = self::MPEB_LABELS[$prodotto] ?? '';

            $values = [
                (int) ($row?->intermediari_convenzionati ?? 0),
                (int) ($row?->intermediari_non_convenzionati ?? 0),
                (int) ($row?->pratiche_intermediate ?? 0),
                (int) ($row?->pratiche_lavorazione ?? 0),
                (float) ($row?->erogato_lordo ?? 0),
                (float) ($row?->erogato_lavorazione ?? 0),
                (float) ($row?->provv_clientela ?? 0),
                (float) ($row?->provv_istituto_comp ?? 0),
                (float) ($row?->premi_istituto_comp ?? 0),
                (float) ($row?->payin_ass_banche ?? 0),
                (float) ($row?->payin_ass_broker ?? 0),
                (float) ($row?->payin_ass_broker_cap ?? 0),
                (float) ($row?->payout_rete_credito ?? 0),
                (float) ($row?->payout_rete_ass_banche ?? 0),
                (float) ($row?->payout_rete_ass_broker ?? 0),
                (float) ($row?->payout_rete_ass_broker_cap ?? 0),
                (int) ($row?->num_rivalse ?? 0),
                (float) ($row?->importo_retrocesse ?? 0),
            ];

            foreach ($values as $i => $v) {
                $totals[$i] += $v;
            }

            $dataRows[] = array_merge([$mpeb, $prodotto], $values);
        }

        // Riga totali
        $dataRows[] = array_merge(['', 'TOTALE'], $totals);

        return array_merge($headers, $dataRows);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestCol = $sheet->getHighestColumn();

                // Titolo
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A1:'.$highestCol.'1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF1F497D');
                $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF');

                // Header riga 4
                $sheet->getStyle('A4:'.$highestCol.'4')->getFont()->setBold(true);
                $sheet->getStyle('A4:'.$highestCol.'4')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
                $sheet->getStyle('A4:'.$highestCol.'4')
                    ->getAlignment()->setWrapText(true);

                // Formato valuta per colonne E–T (5–20)
                $highestRow = $sheet->getHighestRow();
                $currencyFormat = '#,##0.00';
                for ($col = 7; $col <= 20; $col++) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    if (in_array($col, [9, 10, 11, 17])) {
                        // Colonne intere
                        continue;
                    }
                    $sheet->getStyle("{$colLetter}5:{$colLetter}{$highestRow}")
                        ->getNumberFormat()->setFormatCode($currencyFormat);
                }

                // Riga totale in grassetto
                $sheet->getStyle('A'.$highestRow.':'.$highestCol.$highestRow)
                    ->getFont()->setBold(true);
                $sheet->getStyle('A'.$highestRow.':'.$highestCol.$highestRow)
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9D9D9');
            },
        ];
    }
}
