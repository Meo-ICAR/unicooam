<?php

namespace App\Filament\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Senza WithStrictNullComparison, PhpSpreadsheet confronta i valori con null
// tramite "==": un valore intero 0 (es. colonne C/D quando un prodotto ha 0
// convenzioni) risulta "== null" e la cella viene lasciata vuota invece di
// scrivere 0.
class M510EconomicoOldSheet implements FromArray, WithEvents, WithStrictNullComparison, WithTitle
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
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
        ];

        // RIGA 2: Intestazioni di primo livello
        $rows[] = [
            "Numero\niscrizione\n(M510)",  // A
            'PRODOTTI/O CREDITIZI/O OGGETTO DELLA CONVENZIONE / SERVIZIO PRESTATO',  // B
            "N° intermediari\nconvenzionati",  // C
            "N° intermediari\nNON convenzionati",  // D
            'PRATICHE',  // E (Unito E2:F2)
            '',          // F
            'EROGATO',   // G (Unito G2:H2)
            '',          // H
            "TOTALE PROVVIGIONI\nRICONOSCIUTE DALLA\nCLIENTELA",  // I
            "TOTALE PROVVIGIONI\nRICONOSCIUTE\nDALL'ISTITUTO EROGANTE\n(PRINCIPIO DI COMPETENZA)",  // J
            "TOTALE PREMI (QUALITATIVI E\nQUANTITATIVI) RICONOSCIUTI\nDALL'ISTITUTO EROGANTE\n(PRINCIPIO DI COMPETENZA)",  // K
            "(PAY-IN)\nPROVVIGIONI ASSICURATIVE MATURATE -\nProduzione assicurativa - creditizia\n\n(PRINCIPIO DI COMPETENZA)",  // L (Unito L2:N2)
            '', '',
            "AMMONTARE DELLE\nPROVVIGIONI RICONOSCIUTE\nALLA RETE -\nINTERMEDIAZIONE DEL\nCREDITO\n(PRINCIPIO DI COMPETENZA)",  // O
            "(PAY-OUT)\nAMMONTARE DELLE PROVVIGIONI RICONOSCIUTE ALLA RETE -\nINTERMEDIAZIONE ASSICURATIVA\n\n(PRINCIPIO DI COMPETENZA)",  // P (Unito P2:R2)
            '', '',
            "N° RIVALSE AI SENSI DEL'ART.\n125 - SEXIES, DEL TUB",  // S
            "AMMONTARE DELLE\nPROVVIGIONI RETROCESSE AL\nFINANZIATORE IN SEGUITO\nALLA RIVALSA",  // T
        ];

        // RIGA 3: Intestazioni di secondo livello
        $rows[] = [
            '', '', '', '', // A, B, C, D vertical merged
            "N° Pratiche intermediate per\nprodotto/servizio",  // E
            "N° Pratiche di\nfinanziamento in\nlavorazione",  // F
            "Montante lordo / Importo\nerogato per prodotto",  // G
            "Valore delle pratiche di\nfinanziamento in\nlavorazione",  // H
            '', '', '', // I, J, K vertical merged
            "da banche/Intermediari\nfinanziari",  // L
            'da Broker',  // M
            'da Broker Captive',  // N
            '', // O vertical merged
            "da banche/Intermediari\nfinanziari",  // P
            'da Broker',  // Q
            'da Broker Captive',  // R
            '', '', // S, T vertical merged
        ];

        // RIGHE DATI: versione sintetica, senza il dettaglio per istituto erogante
        foreach ($this->prodotti as $index => $row) {
            $numeroMPEB = 'MPEB'.($index + 1);

            $rows[] = [
                $numeroMPEB,  // A
                $row['prodotto_creditizio'] ?? '',  // B
                $row['intermediari_convenzionati'] ?? '',  // C - N° intermediari convenzionati
                $row['intermediari_non_convenzionati'] ?? '',  // D - N° intermediari NON convenzionati
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
                $row['importo_retrocesse'] ?? '0.00',  // T
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

                // Merge orizzontali righe 1 e 2
                $sheet->mergeCells('A1:T1');
                $sheet->mergeCells('E2:F2');
                $sheet->mergeCells('G2:H2');
                $sheet->mergeCells('L2:N2'); // PAY-IN (L, M, N)
                $sheet->mergeCells('P2:R2'); // PAY-OUT (P, Q, R)

                // Merge verticali per le colonne singole (Righe 2 e 3)
                $colonneSingole = ['A', 'B', 'C', 'D', 'I', 'J', 'K', 'O', 'S', 'T'];
                foreach ($colonneSingole as $col) {
                    $sheet->mergeCells("{$col}2:{$col}3");
                }

                // Stile Titolo Principale (Teal scuro)
                $sheet->getStyle('A1:T1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF008080']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Stile Intestazioni Tabella (Teal / Verde Acqua)
                $sheet->getStyle('A2:T3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF000000']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF48D1CC']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']],
                    ],
                ]);

                // Stile Corpo Dati
                if ($highestRow >= 4) {
                    $sheet->getStyle('A4:T'.$highestRow)->applyFromArray([
                        'font' => ['size' => 9],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE0E0E0']],
                        ],
                    ]);

                    $sheet->getStyle('A4:A'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('C4:D'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E4:T'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Impostazione larghezze colonne
                $sheet->getColumnDimension('A')->setWidth(14);
                $sheet->getColumnDimension('B')->setWidth(40);
                $sheet->getColumnDimension('C')->setWidth(16);
                $sheet->getColumnDimension('D')->setWidth(16);

                foreach (range('E', 'T') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(18);
                }
            },
        ];
    }
}
