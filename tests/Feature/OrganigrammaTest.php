<?php

namespace Tests\Feature;

use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\EmployeeTypes\Pages\EditEmployeeType;
use App\Filament\Resources\EmployeeTypes\Pages\ViewEmployeeType;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganigrammaTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_the_office_data_and_the_manager_of_a_role_are_edited_here(): void
    {
        $role = EmployeeType::query()->create(['name' => 'compliance']);
        $manager = Employee::query()->create(['name' => 'Maiello', 'email' => 'maiello@example.com', 'employee_roles' => ['compliance']]);

        Livewire::test(EditEmployeeType::class, ['record' => $role->getKey()])
            ->fillForm([
                'code' => 'COMPLIANCE',
                'description' => 'Funzione Compliance',
                'macro_area' => 'Controlli (II Livello)',
                'outsourcable_status' => 'yes',
                'managed_by_code' => 'CDA',
                'email' => 'compliance@example.com',
                'manager_type' => 'employee',
                'manager_id' => $manager->getKey(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $role->refresh();
        $this->assertSame('COMPLIANCE', $role->code);
        $this->assertSame('yes', $role->outsourcable_status);
        $this->assertSame('CDA', $role->managed_by_code);
        $this->assertTrue($role->manager->is($manager));

        Livewire::test(ViewEmployeeType::class, ['record' => $role->getKey()])->assertSee('Maiello');
    }

    public function test_an_external_receives_only_outsourcable_roles_and_keeps_the_others(): void
    {
        EmployeeType::query()->create(['name' => 'compliance', 'code' => 'COMPLIANCE', 'outsourcable_status' => 'yes']);
        EmployeeType::query()->create(['name' => 'back office', 'code' => 'BUS-BO', 'outsourcable_status' => 'no']);
        $client = Client::query()->create(['name' => 'Studio Allegretti', 'email' => 'studio@example.com', 'employee_roles' => ['SOS']]);

        Livewire::test(ListEmployees::class)
            ->callAction(TestAction::make('ruoliEsterno'), ['client_id' => $client->getKey(), 'roles' => ['compliance']])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertEqualsCanonicalizing(['SOS', 'compliance'], $client->fresh()->employee_roles);
    }

    public function test_the_chart_shows_the_office_of_each_person_and_filters_by_role(): void
    {
        EmployeeType::query()->create(['name' => 'compliance', 'code' => 'COMPLIANCE', 'description' => 'Funzione Compliance']);
        $maiello = Employee::query()->create(['name' => 'Maiello', 'employee_roles' => ['compliance']]);
        $voce = Employee::query()->create(['name' => 'Voce', 'employee_roles' => ['commerciale']]);

        Livewire::test(ListEmployees::class)
            ->removeTableFilter('semestre_attuale')
            ->assertSee('Funzione Compliance')
            ->filterTable('employee_roles', 'compliance')
            ->assertCanSeeTableRecords([$maiello])
            ->assertCanNotSeeTableRecords([$voce]);
    }
}
