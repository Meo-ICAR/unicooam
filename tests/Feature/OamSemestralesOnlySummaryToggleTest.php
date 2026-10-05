<?php

namespace Tests\Feature;

use App\Filament\Resources\OamSemestrales\Pages\ListOamSemestrales;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OamSemestralesOnlySummaryToggleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_onlysummary_defaults_to_true_and_the_toggle_action_flips_it(): void
    {
        Company::factory()->create();
        $this->actingAs(User::factory()->create());

        Livewire::test(ListOamSemestrales::class)
            ->assertSet('onlySummary', true)
            ->callAction('toggleOnlySummary')
            ->assertSet('onlySummary', false)
            ->callAction('toggleOnlySummary')
            ->assertSet('onlySummary', true);
    }

    public function test_table_groups_only_by_prodotto_by_default_and_stops_when_the_flag_is_off(): void
    {
        Company::factory()->create();
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(ListOamSemestrales::class);

        $table = $component->instance()->getTable();
        $this->assertTrue($table->isGroupsOnly());
        $this->assertSame('prodotto_creditizio', $table->getDefaultGroup()?->getId());

        $component->callAction('toggleOnlySummary');

        $table = $component->instance()->getTable();
        $this->assertFalse($table->isGroupsOnly());
        $this->assertNull($table->getDefaultGroup());
    }
}
