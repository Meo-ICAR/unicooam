<?php

namespace Tests\Feature;

use App\Filament\Resources\OamPratiches\Pages\ListOamPratiches;
use App\Filament\Resources\OamSemestrales\Pages\ListOamSemestrales;
use App\Models\Company;
use App\Models\OamPratiche;
use App\Models\OamSemestrale;
use App\Models\User;
use App\ValueObjects\OamSemester;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportOamActionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_oam_semestrales_list_page_renders(): void
    {
        Company::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('filament.admin.resources.oam-semestrales.index'));

        $response->assertOk();
    }

    public function test_import_action_is_registered_on_list_page(): void
    {
        Company::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('filament.admin.resources.oam-semestrales.index'));

        $response->assertOk();
        $response->assertSee('Importa Pratiche');
    }

    public function test_export_action_is_registered_on_list_page(): void
    {
        Company::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('filament.admin.resources.oam-semestrales.index'));

        $response->assertOk();
        $response->assertSee('Completo');
    }

    public function test_base_export_action_is_registered_on_list_page(): void
    {
        Company::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('filament.admin.resources.oam-semestrales.index'));

        $response->assertOk();
        $response->assertSee('Base');
    }

    public function test_list_page_uses_the_default_full_width_breadcrumbs_and_heading(): void
    {
        $page = new ListOamSemestrales;

        $this->assertTrue($page->hasResourceBreadcrumbs());
    }

    public function test_list_page_heading_renders_on_its_own_row_above_the_header_actions(): void
    {
        Company::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('filament.admin.resources.oam-semestrales.index'));

        $response->assertOk();
        $response->assertSee('flex-direction: column', false);
    }

    public function test_list_page_shows_the_convenzione_column(): void
    {
        Company::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('filament.admin.resources.oam-semestrales.index'));

        $response->assertOk();
        $response->assertSee('Convenzione');
    }

    /**
     * @param  non-empty-string  $expectedStart
     * @param  non-empty-string  $expectedEnd
     */
    #[DataProvider('semestreProvider')]
    public function test_semester_date_ranges_are_correct(int $semestre, string $expectedStart, string $expectedEnd): void
    {
        $anno = 2025;

        if ($semestre === 1) {
            $startAt = Carbon::create($anno, 1, 1)->startOfDay();
            $endAt = Carbon::create($anno, 6, 30)->endOfDay();
        } else {
            $startAt = Carbon::create($anno, 7, 1)->startOfDay();
            $endAt = Carbon::create($anno, 12, 31)->endOfDay();
        }

        $this->assertSame($expectedStart, $startAt->format('Y-m-d'));
        $this->assertSame($expectedEnd, $endAt->format('Y-m-d'));
    }

    public static function semestreProvider(): array
    {
        return [
            'primo semestre' => [1, '2025-01-01', '2025-06-30'],
            'secondo semestre' => [2, '2025-07-01', '2025-12-31'],
        ];
    }

    public function test_semestre_filter_limits_rows_and_is_carried_to_detail_link(): void
    {
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['company_id' => $company->id]));

        foreach (['202606', '202612'] as $period) {
            OamSemestrale::forceCreate(['company_id' => $company->id, 'period' => $period, 'abi_name' => 'Banca '.$period, 'prodotto_creditizio' => 'A.2']);
        }

        $rows = fn (string $period) => OamSemestrale::where('period', $period)->first();

        Livewire::test(ListOamSemestrales::class)
            ->set('onlySummary', false)
            ->assertCanSeeTableRecords([$rows('202612')])
            ->assertCanNotSeeTableRecords([$rows('202606')])
            ->filterTable('period', '202606')
            ->assertCanSeeTableRecords([$rows('202606')])
            ->assertCanNotSeeTableRecords([$rows('202612')]);
    }

    public function test_oam_dettaglio_excludes_pratiche_outside_the_semester(): void
    {
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['company_id' => $company->id]));

        $make = fn (string $pratica, ?string $inserita, ?string $erogata) => OamPratiche::forceCreate([
            'company_id' => $company->id, 'period' => '202606', 'pratica' => $pratica, 'sended_at' => $inserita, 'erogated_at' => $erogata,
            'abi_name' => 'Banca X', 'prodotto_creditizio' => 'A.2', 'tipo_prodotto' => 'Cessione', 'istituto' => 'Banca X', 'agente' => 'Agente X',
        ]);

        $dentro = $make('P1', '2026-04-01', '2026-05-12');
        $inserita_a_luglio = $make('P2', '2026-07-10', null);
        $erogata_prima = $make('P3', '2025-11-01', '2025-12-01');

        Livewire::test(ListOamPratiches::class)
            ->filterTable('period', '202606')
            ->assertCanSeeTableRecords([$dentro])
            ->assertCanNotSeeTableRecords([$inserita_a_luglio, $erogata_prima]);
    }

    public function test_import_sentovia_asks_for_the_two_files_and_imports_them(): void
    {
        Storage::fake('local');

        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['company_id' => $company->id]));
        DB::table('oam_codes')->insert(['code' => 'A.2', 'name' => 'Cessioni', 'description' => 'A.2 Cessioni', 'tipo_prodotto' => 'Cessione', 'is_dummy' => 0, 'is_active' => 0]);
        DB::connection('mysql_proforma')->setPdo(DB::connection()->getPdo());

        $pratiche = UploadedFile::fake()->createWithContent('pratiche.txt', implode("\r\n", [
            "ID Pratica\tData Inserimento Pratica\tData_erogazione\tData Annullamento\tStato Pratica\tTipo Prodotto\tDenominazione Istituto\tImporto_Erogazione",
            "P1\t04/07/2026\t12/08/2026\t\tLiquidata\tCessione\tBanca X\t1000,00",
        ]));
        $provvigioni = UploadedFile::fake()->createWithContent('provvigioni.txt', implode("\r\n", [
            "ID Pratica\tTipo\tTipologia Premio\tImporto\tData Status",
            "P1\tIstituto\tCREDITO\t50,00\t12/08/2026",
        ]));

        Livewire::test(ListOamSemestrales::class)
            ->callAction('importSentovia', ['pratiche' => $pratiche, 'provvigioni' => $provvigioni])
            ->assertHasNoActionErrors();

        $this->assertSame(['P1'], OamPratiche::pluck('pratica')->all());
        $this->assertSame([], Storage::disk('local')->allFiles('sentovia/upload'));
    }

    #[DataProvider('currentSemesterProvider')]
    public function test_current_semester_follows_the_calendar(string $date, string $expectedPeriod): void
    {
        $this->assertSame($expectedPeriod, OamSemester::current(CarbonImmutable::parse($date))->period());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function currentSemesterProvider(): array
    {
        return [
            'gennaio: ultimo semestre anno precedente' => ['2027-01-15', '202612'],
            'febbraio: ultimo semestre anno precedente' => ['2027-02-28', '202612'],
            'luglio: primo semestre' => ['2026-07-01', '202606'],
            'ottobre: primo semestre' => ['2026-10-31', '202606'],
            'novembre: secondo semestre' => ['2026-11-04', '202612'],
        ];
    }

    public function test_oam_dettaglio_highlights_pratiche_in_lavorazione(): void
    {
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['company_id' => $company->id]));

        OamPratiche::forceCreate([
            'company_id' => $company->id, 'period' => '202606', 'pratica' => 'P1', 'pratiche_lavorazione' => 1,
            'abi_name' => 'Banca X', 'prodotto_creditizio' => 'A.2', 'tipo_prodotto' => 'Cessione', 'istituto' => 'Banca X', 'agente' => 'Agente X',
        ]);

        Livewire::test(ListOamPratiches::class)
            ->filterTable('period', '202606')
            ->assertSeeHtml('oam-pratica-lavorazione');
    }
}
