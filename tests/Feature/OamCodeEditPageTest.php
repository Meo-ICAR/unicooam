<?php

namespace Tests\Feature;

use App\Models\OamCode;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OamCodeEditPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_edit_page_loads_without_a_pivot_table_connection_error(): void
    {
        $oamCode = OamCode::create([
            'code' => 'A.1',
            'name' => 'Mutui',
            'description' => 'A.1 Mutui',
            'tipo_prodotto' => 'Mutuo',
            'is_dummy' => false,
            'is_active' => true,
        ]);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('filament.admin.resources.oam-codes.edit', ['record' => $oamCode->id]));

        $response->assertOk();
    }
}
