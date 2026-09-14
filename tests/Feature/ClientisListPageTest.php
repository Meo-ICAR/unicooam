<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClientisListPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_list_page_loads_and_shows_the_n_prodotti_count_column(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('filament.admin.resources.clientis.index'));

        $response->assertOk();
        $response->assertSee('N. Prodotti');
    }
}
