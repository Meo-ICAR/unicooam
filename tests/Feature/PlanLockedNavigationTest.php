<?php

namespace Tests\Feature;

use App\Filament\Resources\Clientis\ClientiResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\OamSemestrales\Pages\ListOamSemestrales;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PlanLockedNavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function resource(string $key, string $minPlan): void
    {
        DB::table('resources')->insert([
            'app_name' => config('app.name'),
            'key' => $key,
            'name' => $key,
            'min_plan' => $minPlan,
        ]);
    }

    public function test_menu_item_not_covered_by_plan_is_shown_struck_through_and_not_reachable(): void
    {
        config(['unico-core.access.plan' => 'BASE']);
        $this->resource('document-schedules', 'MEDIUM');
        $this->resource('oam-codes', 'BASE');

        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['company_id' => $company->id, 'role' => 'admin']));

        $page = $this->get(route('filament.admin.resources.oam-semestrales.index'));

        $page->assertOk();
        $page->assertSee('Scadenziario');
        $page->assertSee('text-decoration:line-through', false);
        $page->assertDontSee(route('filament.admin.resources.document-schedules.index'), false);
        $page->assertSee(route('filament.admin.resources.oam-codes.index'), false);

        $this->get(route('filament.admin.resources.document-schedules.index'))->assertForbidden();
    }

    public function test_export_actions_not_covered_by_plan_are_disabled_on_semestrale_page(): void
    {
        config(['unico-core.access.plan' => 'BASE']);
        $this->resource('export_completo', 'MEDIUM');

        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['company_id' => $company->id, 'role' => 'admin']));

        Livewire::test(ListOamSemestrales::class)
            ->assertActionDisabled('export_completo')
            ->assertActionEnabled('Base');
    }

    public function test_relation_managers_of_company_and_clienti_are_unavailable_with_base_plan(): void
    {
        config(['unico-core.access.plan' => 'BASE']);
        $this->resource('companies-relations', 'MEDIUM');
        $this->resource('clientis-relations', 'MEDIUM');

        $this->actingAs(User::factory()->create(['company_id' => Company::factory()->create()->id, 'role' => 'admin']));

        $this->assertSame([], CompanyResource::getRelations());
        $this->assertSame([], ClientiResource::getRelations());
    }
}
