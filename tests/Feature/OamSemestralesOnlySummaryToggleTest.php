<?php

namespace Tests\Feature;

use App\Filament\Resources\OamSemestrales\Pages\ListOamSemestrales;
use App\Models\Company;
use App\Models\OamSemestrale;
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

    public function test_finanziatore_column_is_hidden_in_summary_and_shown_with_detail(): void
    {
        Company::factory()->create();
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(ListOamSemestrales::class);

        $this->assertTrue($component->instance()->getTable()->getColumn('abi_name')->isHidden());

        $component->callAction('toggleOnlySummary');

        $this->assertFalse($component->instance()->getTable()->getColumn('abi_name')->isHidden());
    }

    public function test_prodotto_group_uses_natural_order_with_uncoded_products_last(): void
    {
        $company = Company::factory()->create();

        foreach (['Segnalazione Mutuo', 'A.10 Credito personale', 'A.2 Cessioni', 'A.1 Mutui', 'A.4bis TFS'] as $prodotto) {
            OamSemestrale::factory()->create(['company_id' => $company->id, 'prodotto_creditizio' => $prodotto]);
        }

        $table = Livewire::actingAs(User::factory()->create())
            ->test(ListOamSemestrales::class)
            ->instance()
            ->getTable();

        $query = OamSemestrale::query();
        $table->getGroup('prodotto_creditizio')->orderQuery($query, 'asc');

        $this->assertSame(
            ['A.1 Mutui', 'A.2 Cessioni', 'A.4bis TFS', 'A.10 Credito personale', 'Segnalazione Mutuo'],
            $query->pluck('prodotto_creditizio')->all(),
        );
    }
}
