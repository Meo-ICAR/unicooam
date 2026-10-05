<?php

namespace Tests\Unit;

use App\Filament\Exports\Sheets\M510EconomicoOldSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class M510EconomicoOldSheetTest extends TestCase
{
    private function rows(): array
    {
        return [
            [
                'prodotto_creditizio' => 'A.1 Mutui',
                'pratiche_intermediate' => 2,
                'pratiche_lavorazione' => 6,
                'erogato_lordo' => '110000.00',
                'erogato_lavorazione' => '560000.00',
                'provv_clientela' => '3700.00',
                'provv_istituto_comp' => '2910.00',
                'premi_istituto_comp' => '0.00',
                'payin_ass_banche' => '0.00',
                'payin_ass_broker' => '0.00',
                'payin_ass_broker_cap' => '0.00',
                'payout_rete_credito' => '3547.50',
                'payout_rete_ass_banche' => '0.00',
                'payout_rete_ass_broker' => '0.00',
                'payout_rete_ass_broker_cap' => '0.00',
                'num_rivalse' => 0,
                'importo_retrocesse' => '0.00',
                'intermediari_convenzionati' => 12,
                'intermediari_non_convenzionati' => 0,
            ],
        ];
    }

    public function test_data_rows_map_the_convenzioni_columns_and_remaining_fields(): void
    {
        $sheet = new M510EconomicoOldSheet($this->rows());

        $rows = $sheet->array();

        // Riga 1: titolo, riga 2 e 3: intestazioni, riga 4: primo (e unico) dato.
        $this->assertCount(4, $rows);

        foreach ($rows as $row) {
            $this->assertCount(20, $row, 'Ogni riga deve avere 20 colonne (A..T)');
        }

        $dataRow = $rows[3];

        $this->assertSame('MPEB1', $dataRow[0]);
        $this->assertSame('A.1 Mutui', $dataRow[1]);
        $this->assertSame(12, $dataRow[2], 'Colonna C: N. intermediari convenzionati');
        $this->assertSame(0, $dataRow[3], 'Colonna D: N. intermediari NON convenzionati');
        $this->assertSame(2, $dataRow[4]);
        $this->assertSame('110000.00', $dataRow[6]);
    }

    public function test_a_zero_value_in_columns_c_and_d_survives_the_excel_export(): void
    {
        // Regressione: senza WithStrictNullComparison, PhpSpreadsheet confronta
        // i valori con null tramite "==" e un intero 0 (0 convenzioni) viene
        // trattato come "vuoto", lasciando la cella bianca invece di scrivere 0.
        $sheet = new M510EconomicoOldSheet($this->rows());

        $export = new class($sheet) implements WithMultipleSheets
        {
            use Exportable;

            public function __construct(private M510EconomicoOldSheet $sheet) {}

            public function sheets(): array
            {
                return [$this->sheet];
            }
        };

        $filePath = 'test-m510-economico-old-'.uniqid().'.xlsx';
        Excel::store($export, $filePath, 'local');

        $spreadsheet = IOFactory::load(storage_path('app/private/'.$filePath));
        $worksheet = $spreadsheet->getActiveSheet();

        $this->assertSame(12, $worksheet->getCell('C4')->getValue());
        $this->assertSame(0, $worksheet->getCell('D4')->getValue());

        unlink(storage_path('app/private/'.$filePath));
    }
}
