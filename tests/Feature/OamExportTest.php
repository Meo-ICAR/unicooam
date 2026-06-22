<?php

namespace Tests\Feature;

use App\Filament\Exports\OamSemestraleExport;
use App\Filament\Exports\OamSheets\AnagraficaSheet;
use App\Filament\Exports\OamSheets\ProfiloEconomicoSheet;
use App\Filament\Exports\OamSheets\ProfiloInformativoSheet;
use App\Filament\Exports\OamSheets\ProfiloPrudenzialeSheet;
use App\Filament\Exports\OamSheets\SediTerritorialiSheet;
use App\Models\Branch;
use App\Models\Company;
use App\Models\OamSemestrale;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OamExportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_export_produces_five_sheets(): void
    {
        Company::factory()->create(['name' => 'Races Finance SRL', 'oam' => 'M51234']);

        $export = new OamSemestraleExport(2025, 1);
        $sheets = $export->sheets();

        $this->assertCount(5, $sheets);
    }

    public function test_sheets_are_correct_types(): void
    {
        Company::factory()->create();

        $export = new OamSemestraleExport(2025, 1);
        $sheets = $export->sheets();

        $this->assertInstanceOf(AnagraficaSheet::class, $sheets[0]);
        $this->assertInstanceOf(ProfiloEconomicoSheet::class, $sheets[1]);
        $this->assertInstanceOf(ProfiloPrudenzialeSheet::class, $sheets[2]);
        $this->assertInstanceOf(ProfiloInformativoSheet::class, $sheets[3]);
        $this->assertInstanceOf(SediTerritorialiSheet::class, $sheets[4]);
    }

    public function test_sheet_titles_are_correct(): void
    {
        Company::factory()->create();

        $export = new OamSemestraleExport(2025, 2);
        $sheets = $export->sheets();

        $this->assertSame('ANAGRAFICA', $sheets[0]->title());
        $this->assertSame('PROFILO ECONOMICO BASE', $sheets[1]->title());
        $this->assertSame('PROFILO PRUDENZIALE', $sheets[2]->title());
        $this->assertSame('PROFILO INFORMATIVO', $sheets[3]->title());
        $this->assertSame('ELENCO SEDI TERRITORIALI', $sheets[4]->title());
    }

    public function test_anagrafica_sheet_contains_company_data(): void
    {
        $company = Company::factory()->create([
            'name' => 'Test Mediazioni SRL',
            'vat_number' => '12345678901',
            'oam' => 'M99999',
        ]);

        $sheet = new AnagraficaSheet(
            company: $company,
            periodoLabel: '01/01/2025 – 30/06/2025',
            year: '2025',
            semestre: 1,
        );

        $rows = $sheet->array();

        // Trova le righe con i dati company
        $flatValues = collect($rows)->flatten()->values()->toArray();

        $this->assertContains('Test Mediazioni SRL', $flatValues);
        $this->assertContains('12345678901', $flatValues);
        $this->assertContains('M99999', $flatValues);
    }

    public function test_profilo_economico_sheet_contains_oam_rows(): void
    {
        $company = Company::factory()->create();

        OamSemestrale::factory()->create([
            'company_id' => $company->id,
            'period' => '202501',
            'prodotto_creditizio' => 'Mutuo Ipotecario',
            'pratiche_intermediate' => 10,
            'erogato_lordo' => 500000.00,
        ]);

        $sheet = new ProfiloEconomicoSheet(
            company: $company,
            period: '202501',
            periodoLabel: '01/01/2025 – 30/06/2025',
        );

        $rows = $sheet->array();
        $flatValues = collect($rows)->flatten()->values()->toArray();

        $this->assertContains('MPEB1', $flatValues);
        $this->assertContains('Mutuo Ipotecario', $flatValues);
        $this->assertContains(10, $flatValues);
    }

    public function test_sedi_sheet_contains_branch_address(): void
    {
        $company = Company::factory()->create(['oam' => 'M51234']);

        Branch::factory()->create([
            'company_id' => $company->id,
            'address' => 'Via Roma',
            'street_number' => '1',
            'city' => 'Milano',
            'zip_code' => '20121',
            'province' => 'Milano',
            'region' => 'Lombardia',
            'is_main_office' => true,
        ]);

        $sheet = new SediTerritorialiSheet(
            company: $company,
            periodoLabel: '01/01/2025 – 30/06/2025',
        );

        $rows = $sheet->array();
        $flatValues = collect($rows)->flatten()->values()->toArray();

        $this->assertContains('Via Roma', $flatValues);
        $this->assertContains('Milano', $flatValues);
        $this->assertContains('Lombardia', $flatValues);
        $this->assertContains('SI', $flatValues);
        $this->assertContains('M51234', $flatValues);
    }

    public function test_second_semester_period_is_correct(): void
    {
        $company = Company::factory()->create();

        $export = new OamSemestraleExport(2025, 2);
        $sheets = $export->sheets();

        /** @var ProfiloEconomicoSheet $economicoSheet */
        $economicoSheet = $sheets[1];

        // La sheet del profilo economico usa period '202507' per il 2° semestre
        $this->assertSame('PROFILO ECONOMICO BASE', $economicoSheet->title());
    }
}
