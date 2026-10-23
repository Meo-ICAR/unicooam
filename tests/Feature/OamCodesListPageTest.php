<?php

namespace Tests\Feature;

use App\Filament\Resources\OamCodes\Pages\ListOamCodes;
use App\Models\OamCode;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class OamCodesListPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_list_page_loads_and_shows_the_convenzioni_count_column(): void
    {
        OamCode::create([
            'code' => 'A.1',
            'name' => 'Mutui',
            'description' => 'A.1 Mutui',
            'tipo_prodotto' => 'Mutuo',
            'is_dummy' => false,
            'is_active' => true,
        ]);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('filament.admin.resources.oam-codes.index'));

        $response->assertOk();
        $response->assertSee('N. Convenzioni');
    }

    public function test_convenzioni_filter_splits_products_with_and_without_a_convenzione(): void
    {
        $conProdotto = OamCode::create([
            'code' => 'TEST.CON',
            'name' => 'Con convenzione',
            'description' => 'TEST.CON Con convenzione',
            'tipo_prodotto' => 'Test',
            'is_dummy' => false,
            'is_active' => true,
        ]);
        $senzaProdotto = OamCode::create([
            'code' => 'TEST.SENZA',
            'name' => 'Senza convenzione',
            'description' => 'TEST.SENZA Senza convenzione',
            'tipo_prodotto' => 'Test',
            'is_dummy' => false,
            'is_active' => true,
        ]);

        // Gli id di clientis sono interi (schema condiviso): si inserisce senza passare dal modello, che genera UUID.
        $clienteId = DB::connection('mysql_proforma')->table('clientis')->insertGetId(['name' => 'Banca di prova', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        DB::connection('mysql')->table('clienti_oam')->insert([
            'clienti_id' => $clienteId,
            'oam_code_id' => $conProdotto->id,
        ]);

        $this->actingAs(User::factory()->create());

        // Si cerca per prefisso così gli altri codici presenti nella tabella non spingono i due di prova su un'altra pagina.
        Livewire::test(ListOamCodes::class)
            ->searchTable('TEST.')
            ->filterTable('clienti_count', true)
            ->assertCanSeeTableRecords([$conProdotto])
            ->assertCanNotSeeTableRecords([$senzaProdotto])
            ->filterTable('clienti_count', false)
            ->assertCanSeeTableRecords([$senzaProdotto])
            ->assertCanNotSeeTableRecords([$conProdotto]);
    }
}
