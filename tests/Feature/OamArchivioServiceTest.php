<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\OamPratiche;
use App\Models\OamSemestrale;
use App\Services\OamArchivioService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OamArchivioServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archivia_copies_only_the_given_period_and_keeps_working_tables(): void
    {
        $company = Company::factory()->create();
        $this->seedRows($company, '202601');
        $this->seedRows($company, '202507');

        $archivio = app(OamArchivioService::class)->archivia('202601', $company->id, 'chiusura');

        $this->assertSame(1, $archivio->semestrali_count);
        $this->assertSame(2, $archivio->pratiche_count);
        $this->assertSame(3, $archivio->righe()->count());
        $this->assertSame(2, OamSemestrale::count());
        $this->assertSame(4, OamPratiche::count());
    }

    public function test_richiama_replaces_current_rows_with_archived_ones(): void
    {
        $company = Company::factory()->create();
        $this->seedRows($company, '202601');
        $service = app(OamArchivioService::class);

        $archivio = $service->archivia('202601', $company->id, svuota: true);
        $this->assertSame(0, OamSemestrale::where('period', '202601')->count());
        $this->assertSame(0, OamPratiche::where('period', '202601')->count());

        OamPratiche::create(['company_id' => $company->id, 'period' => '202601', 'pratica' => 'EXTRA']);

        $ripristinate = $service->richiama($archivio);

        $this->assertSame(['semestrali' => 1, 'pratiche' => 2], $ripristinate);
        $this->assertSame(2, OamPratiche::where('period', '202601')->count());
        $this->assertSame('1500.00', OamSemestrale::where('period', '202601')->first()->erogato_lordo);
        $this->assertNotNull($archivio->fresh()->restored_at);
    }

    private function seedRows(Company $company, string $period): void
    {
        OamSemestrale::create([
            'company_id' => $company->id,
            'period' => $period,
            'abi_name' => 'Banca Test',
            'prodotto_creditizio' => 'A.1',
            'erogato_lordo' => 1500,
        ]);

        foreach (['P1', 'P2'] as $codice) {
            OamPratiche::create(['company_id' => $company->id, 'period' => $period, 'pratica' => "{$codice}-{$period}"]);
        }
    }
}
