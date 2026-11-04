<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\OamPratiche;
use App\Services\ImportSentoviaService;
use App\ValueObjects\OamSemester;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportSentoviaServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_imports_pratiche_and_provvigioni_from_files(): void
    {
        Company::factory()->create();

        // Stessa connessione della transazione del test: evita il lock sulla company.
        DB::connection('mysql_proforma')->setPdo(DB::connection()->getPdo());

        DB::table('oam_codes')->insert(['code' => 'A.2', 'name' => 'Cessioni del V', 'description' => 'A.2 Cessioni del V', 'tipo_prodotto' => 'Cessione', 'is_dummy' => 0, 'is_active' => 0]);
        DB::table('oam_codes')->insert(['code' => 'A.10', 'name' => 'Credito personale', 'description' => 'A.10 Credito personale', 'tipo_prodotto' => 'CREDITO', 'is_dummy' => 0, 'is_active' => 0]);
        DB::table('oam_codes')->insert(['code' => 'A.9', 'name' => 'Fido', 'description' => 'A.9 Fido', 'tipo_prodotto' => 'Fido', 'is_dummy' => 0, 'is_active' => 0]);

        $dir = sys_get_temp_dir().'/sentovia_'.uniqid();
        mkdir($dir);
        file_put_contents("$dir/p.txt", implode("\r\n", [
            "ID Pratica\tData Inserimento Pratica\tData_erogazione\tData Annullamento\tStato Pratica\tTipo Prodotto\tDenominazione Istituto\tImporto_Erogazione",
            "P1\t04/07/2026\t12/08/2026\t\tLiquidata\tCessione\tIstituto X\t33.480,00",
            "P2\t05/07/2026\t\t\tPreventivo\tCessione\tIstituto X\t1000,00",
            "P3\t06/07/2026\t\t20/07/2026\tRespinta\tCessione\tIstituto X\t1000,00",
            "P5\t01/03/2026\t15/03/2026\t\tLiquidata\tCessione\tIstituto X\t1000,00",
            "P6\t05/01/2027\t\t\tIn istruttoria\tCessione\tIstituto X\t1000,00",
            "P4\t07/07/2026\t\t\tIn istruttoria\tCessione\tIstituto X\t1000,00",
        ]));
        file_put_contents("$dir/v.txt", implode("\r\n", [
            "ID Pratica\tTipo\tTipologia Premio\tImporto\tData Status",
            "P1\tIstituto\tCREDITO\t1091,89\t12/08/2026",
            "P1\tAgente\tCREDITO\t436,76\t12/08/2026",
        ]));

        $count = app(ImportSentoviaService::class)->import(new OamSemester(2026, 2), "$dir/p.txt", "$dir/v.txt");

        $this->assertSame(4, $count);
        $this->assertSame(['P1', 'P4', 'P5', 'P6'], OamPratiche::orderBy('pratica')->pluck('pratica')->all());

        $p1 = OamPratiche::where('pratica', 'P1')->first();
        $this->assertEquals(1091.89, $p1->provv_istituto_comp);
        $this->assertEquals(436.76, $p1->payout_rete_credito);
        $this->assertNull(OamPratiche::where('pratica', 'P4')->value('erogated_at'));
        $this->assertSame('banca', $p1->principal_type);
        // P5 (erogata a marzo) e P6 (inserita nel 2027) restano in oam_pratiches ma fuori dal riepilogo.
        $this->assertEquals(1, DB::table('oam_semestrales')->where('period', '202612')->sum('pratiche_intermediate'));
        $this->assertEquals(1, DB::table('oam_semestrales')->where('period', '202612')->sum('pratiche_lavorazione'));
        $this->assertSame(1, DB::connection('mysql_proforma')->table('clientis')->where('name', 'Istituto X')->count());

        $clienteId = DB::connection('mysql_proforma')->table('clientis')->where('name', 'Istituto X')->value('id');
        $this->assertSame(['Cessione'], DB::table('oam_codes')->where('is_active', 1)->pluck('tipo_prodotto')->all());
        $this->assertSame(1, DB::table('clienti_oam')->where('clienti_id', $clienteId)->count());
    }
}
