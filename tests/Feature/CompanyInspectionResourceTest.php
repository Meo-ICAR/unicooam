<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyIspection;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CompanyInspectionResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_list_page_renders_successfully(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('filament.admin.resources.company-inspections.index'));

        $response->assertOk();
    }

    public function test_create_page_renders_successfully(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('filament.admin.resources.company-inspections.create'));

        $response->assertOk();
    }

    public function test_inspection_can_be_created(): void
    {
        $this->actingAsAdmin();
        $company = Company::factory()->create();

        $inspection = CompanyIspection::factory()->create([
            'company_id' => $company->id,
            'name' => 'Ispezione Test OAM',
            'dal' => '2025-01-15',
            'al' => '2025-01-20',
            'execution_method' => 'documentale',
            'ispectorName' => 'Mario Rossi',
            'n' => 1,
        ]);

        $this->assertModelExists($inspection);
        $this->assertSame('Ispezione Test OAM', $inspection->name);
        $this->assertSame('2025-01-15', $inspection->dal->format('Y-m-d'));
        $this->assertSame('documentale', $inspection->execution_method);
    }

    public function test_edit_page_renders_successfully(): void
    {
        $this->actingAsAdmin();
        $company = Company::factory()->create();
        $inspection = CompanyIspection::factory()->create(['company_id' => $company->id]);

        $response = $this->get(
            route('filament.admin.resources.company-inspections.edit', ['record' => $inspection->id])
        );

        $response->assertOk();
    }

    public function test_company_has_inspections_relation(): void
    {
        $company = Company::factory()->create();
        CompanyIspection::factory()->count(3)->create(['company_id' => $company->id]);

        $this->assertCount(3, $company->inspections);
    }
}
