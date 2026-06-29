<?php

namespace App\Filament\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class M510EconomicoBaseSheet implements FromArray, WithTitle, WithEvents
{
    protected array $prodotti;

    public function __construct(array $prodotti)
    {
        $this->prodotti = $prodotti;
    }

    public function array(): array
    {
        $rows = [];

        // RIGA 1: Titolo principale
        $rows[] = [
            '1 di 5 _ PROFILO ECONOMICO/OPERATIVO BASE',
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
        ];

        // RIGA 2: Intestazioni di primo livello
        $rows[] = [
            "Numero\niscrizione\n(M510)",  // A
            'PRODOTTI/O CREDITIZI/O OGGETTO DELLA CONVENZIONE / SERVIZIO PRESTATO',  // B
            "N°\nintermediari\nconvenzionati",  // C
            "N°\nintermediari\nNON\nconvenzionati",  // D
            'PRATICHE',  // E (Unito con F)
            '',  // F
            'EROGATO',  // G (Unito con H)
            '',  // H
            "TOTALE PROVVIGIONI\nRICONOSCIUTE DALLA\nCLIENTELA",  // I
            "TOTALE PROVVIGIONI\nRICONOSCIUTE\nDALL'ISTITUTO EROGANTE\n(PRINCIPIO DI COMPETENZA)",  // J
            "TOTALE PREMI (QUALITATIVI E\nQUANTITATIVI) RICONOSCIUTI\nDALL'ISTITUTO EROGANTE\n(PRINCIPIO DI COMPETENZA)",  // K
            "(PAY-IN)\nPROVVIGIONI ASSICURATIVE MATURATE -\nProduzione assicurativa - creditizia\n\n(PRINCIPIO DI COMPETENZA)",  // L (Unito con M, N)
            '', '',
            "AMMONTARE DELLE\nPROVVIGIONI RICONOSCIUTE\nALLA RETE -\nINTERMEDIAZIONE DEL\nCREDITO\n(PRINCIPIO DI COMPETENZA)",  // O
            "(PAY-OUT)\nAMMONTARE DELLE PROVVIGIONI RICONOSCIUTE ALLA RETE -\nINTERMEDIAZIONE ASSICURATIVA - CREDITIZIA\n\n(PRINCIPIO DI COMPETENZA)",  // P (Unito con Q, R)
            '', '',
            "N° RIVALSE AI SENSI DELL'ART.\n125 - SEXIES, DEL TUB",  // S
            "AMMONTARE DELLE\nPROVVIGIONI RETROCESSE AL\nFINANZIATORE IN SEGUITO\nALLA RIVALSA"  // T
        ];

        // RIGA 3: Intestazioni di secondo livello
        $rows[] = [
            '', '', '', '',
            "N° Pratiche intermediate per\nprodotto/servizio",  // E
            "N° Pratiche di\nfinanziamento in\nlavorazione",  // F
            "Montante lordo / Importo\nerogato per prodotto",  // G
            "Valore delle pratiche di\nfinanziamento in\nlavorazione",  // H
            '', '', '',
            "da banche/Intermediari\nfinanziari",  // L
            'da Broker',  // M
            'da Broker Captive',  // N
            '',  // O
            "da banche/Intermediari\nfinanziari",  // P
            'da Broker',  // Q
            'da Broker Captive',  // R
            '', ''
        ];

        // RIGHE DATI: Generazione con i nuovi nomi dei campi del DB
        foreach ($this->prodotti as $index => $row) {
            $numeroMPEB = 'MPEB' . ($index + 1);

            $rows[] = [
                $numeroMPEB,  // A
                $row['prodotto_creditizio'] ?? '',  // B
                $row['intermediari_convenzionati'] ?? 0,  // C
                $row['intermediari_non_convenzionati'] ?? 0,  // D
                $row['pratiche_intermediate'] ?? 0,  // E
                $row['pratiche_lavorazione'] ?? 0,  // F
                $row['erogato_lordo'] ?? '0.00',  // G
                $row['erogato_lavorazione'] ?? '0.00',  // H
                $row['provv_clientela'] ?? '0.00',  // I
                $row['provv_istituto_comp'] ?? '0.00',  // J
                $row['premi_istituto_comp'] ?? '0.00',  // K
                $row['payin_ass_banche'] ?? '0.00',  // L
                $row['payin_ass_broker'] ?? '0.00',  // M
                $row['payin_ass_broker_cap'] ?? '0.00',  // N
                $row['payout_rete_credito'] ?? '0.00',  // O
                $row['payout_rete_ass_banche'] ?? '0.00',  // P
                $row['payout_rete_ass_broker'] ?? '0.00',  // Q
                $row['payout_rete_ass_broker_cap'] ?? '0.00',  // R
                $row['num_rivalse'] ?? 0,  // S
                $row['importo_retrocesse'] ?? '0.00'  // T
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Profilo Economico Base';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:T1');
                $sheet->mergeCells('E2:F2');
                $sheet->mergeCells('G2:H2');
                $sheet->mergeCells('L2:N2');
                $sheet->mergeCells('P2:R2');

                $colonneSingole = ['A', 'B', 'C', 'D', 'I', 'J', 'K', 'O', 'S', 'T'];
                foreach ($colonneSingole as $col) {
                    $sheet->mergeCells("{$col}2:{$col}3");
                }

                // Titolo Principale (Teal)
                $sheet->getStyle('A1:T1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF008080']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Intestazioni (Verde Acqua Chiaro)
                $sheet->getStyle('A2:T3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF48D1CC']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']],
                    ]
                ]);

                // Corpo Dati
                if ($highestRow >= 4) {
                    $sheet->getStyle('A4:T' . $highestRow)->applyFromArray([
                        'font' => ['size' => 9],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']]
                    ]);

                    $sheet->getStyle('C4:T' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->getColumnDimension('A')->setWidth(12);
                $sheet->getColumnDimension('B')->setWidth(45);

                foreach (range('C', 'T') as $col) {
                    if ($col !== 'A' && $col !== 'B') {
                        $sheet->getColumnDimension($col)->setWidth(18);
                    }
                }
            },
        ];
    }
}
