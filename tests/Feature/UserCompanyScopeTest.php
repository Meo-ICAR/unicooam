<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\OamPratiche;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserCompanyScopeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_with_company_id_does_not_see_other_company_data(): void
    {
        [$mine, $other] = Company::factory()->count(2)->create();

        foreach ([['P1', $mine->id], ['P2', $other->id], ['P3', null]] as [$pratica, $companyId]) {
            OamPratiche::forceCreate(['pratica' => $pratica, 'company_id' => $companyId, 'period' => '202612']);
        }

        $this->assertSame(3, OamPratiche::count());

        $this->actingAs(User::factory()->create(['company_id' => $mine->id]));

        $this->assertSame(['P1', 'P3'], OamPratiche::orderBy('pratica')->pluck('pratica')->all());
    }

    public function test_user_without_company_id_sees_everything(): void
    {
        [$mine, $other] = Company::factory()->count(2)->create();

        OamPratiche::forceCreate(['pratica' => 'P1', 'company_id' => $mine->id, 'period' => '202612']);
        OamPratiche::forceCreate(['pratica' => 'P2', 'company_id' => $other->id, 'period' => '202612']);

        $this->actingAs(User::factory()->create(['company_id' => null]));

        $this->assertSame(2, OamPratiche::count());
    }

    public function test_user_with_company_id_sees_only_users_of_same_company(): void
    {
        [$mine, $other] = Company::factory()->count(2)->create();

        $me = User::factory()->create(['company_id' => $mine->id]);
        $colleague = User::factory()->create(['company_id' => $mine->id]);
        User::factory()->create(['company_id' => $other->id]);
        User::factory()->create(['company_id' => null]);

        $this->actingAs($me);

        $this->assertEqualsCanonicalizing([$me->id, $colleague->id], User::pluck('id')->all());
    }

    public function test_new_records_get_user_company_id_or_last_company_id(): void
    {
        $company = Company::factory()->create();

        $this->actingAs(User::factory()->create(['company_id' => null, 'last_company_id' => $company->id]));

        $pratica = OamPratiche::create(['pratica' => 'P1', 'period' => '202612']);

        $this->assertSame($company->id, $pratica->company_id);

        $this->actingAs(User::factory()->create(['company_id' => $company->id, 'last_company_id' => null]));

        $this->assertSame($company->id, OamPratiche::create(['pratica' => 'P2', 'period' => '202612'])->company_id);
    }
}
