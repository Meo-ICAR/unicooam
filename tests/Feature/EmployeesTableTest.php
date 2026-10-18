<?php

namespace Tests\Feature;

use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\Employee;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeesTableTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_crea_utente_creates_and_links_user_when_email_is_present(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Filament::setCurrentPanel('admin');
        $employee = Employee::query()->create([
            'name' => 'Maria Rossi',
            'email' => 'Maria.Rossi@example.com',
            'hiring_date' => '2020-01-01',
        ]);

        Livewire::test(ListEmployees::class)
            ->assertTableActionVisible('createUser', $employee)
            ->callTableAction('createUser', $employee)
            ->assertNotified();

        $user = User::query()->where('email', 'maria.rossi@example.com')->sole();
        $employee->refresh();

        $this->assertSame($user->id, $employee->user_id);
        $this->assertSame('employee', $user->role);
        $this->assertSame('employee', $user->profile_type);
        $this->assertSame($employee->id, $user->profile_id);
        $this->assertSame('Maria Rossi', $user->name);
    }

    public function test_crea_utente_stays_hidden_when_user_is_already_linked(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Filament::setCurrentPanel('admin');
        $linked = User::factory()->create(['email' => 'linked@example.com', 'role' => 'employee']);
        $employee = Employee::query()->create([
            'name' => 'Luca Verdi',
            'email' => 'linked@example.com',
            'user_id' => $linked->id,
            'hiring_date' => '2020-01-01',
        ]);

        Livewire::test(ListEmployees::class)
            ->assertTableActionHidden('createUser', $employee);

        $this->assertSame(1, User::query()->where('email', 'linked@example.com')->count());
    }

    public function test_crea_utente_stays_hidden_when_email_is_missing(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Filament::setCurrentPanel('admin');
        $employee = Employee::query()->create([
            'name' => 'Senza Email',
            'email' => '   ',
            'hiring_date' => '2020-01-01',
        ]);

        Livewire::test(ListEmployees::class)
            ->assertTableActionHidden('createUser', $employee);

        $this->assertSame(1, User::query()->count());
    }

    public function test_crea_utente_links_existing_user_with_the_same_email(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Filament::setCurrentPanel('admin');
        $existing = User::factory()->create([
            'name' => 'Account esistente',
            'email' => 'gia.presente@example.com',
            'role' => 'inspector',
        ]);
        $employee = Employee::query()->create([
            'name' => 'Giulia Neri',
            'email' => 'GIA.PRESENTE@example.com',
            'hiring_date' => '2020-01-01',
        ]);

        Livewire::test(ListEmployees::class)
            ->callTableAction('createUser', $employee)
            ->assertNotified();

        $employee->refresh();
        $existing->refresh();

        $this->assertSame($existing->id, $employee->user_id);
        $this->assertSame('inspector', $existing->role);
        $this->assertSame(2, User::query()->count());
    }

    public function test_crea_utente_refuses_email_already_linked_to_another_employee(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Filament::setCurrentPanel('admin');
        $existing = User::factory()->create(['email' => 'condivisa@example.com', 'role' => 'employee']);
        Employee::query()->create([
            'name' => 'Primo',
            'email' => 'condivisa@example.com',
            'user_id' => $existing->id,
            'hiring_date' => '2020-01-01',
        ]);
        $second = Employee::query()->create([
            'name' => 'Secondo',
            'email' => 'condivisa@example.com',
            'hiring_date' => '2020-01-01',
        ]);

        Livewire::test(ListEmployees::class)
            ->callTableAction('createUser', $second)
            ->assertNotified();

        $this->assertNull($second->refresh()->user_id);
        $this->assertSame(2, User::query()->count());
    }

    public function test_crea_utente_keeps_deleted_user_deleted_when_email_is_linked_to_another_employee(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Filament::setCurrentPanel('admin');
        $existing = User::factory()->create(['email' => 'eliminato@example.com', 'role' => 'employee']);
        Employee::query()->create([
            'name' => 'Primo',
            'email' => 'eliminato@example.com',
            'user_id' => $existing->id,
            'hiring_date' => '2020-01-01',
        ]);
        $existing->delete();
        $second = Employee::query()->create([
            'name' => 'Secondo',
            'email' => 'eliminato@example.com',
            'hiring_date' => '2020-01-01',
        ]);

        Livewire::test(ListEmployees::class)
            ->callTableAction('createUser', $second)
            ->assertNotified();

        $this->assertNull($second->refresh()->user_id);
        $this->assertTrue(User::withTrashed()->find($existing->id)->trashed());
    }

    public function test_crea_utente_restores_deleted_user_when_linking_it(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Filament::setCurrentPanel('admin');
        $existing = User::factory()->create(['email' => 'tornato@example.com', 'role' => 'employee']);
        $existing->delete();
        $employee = Employee::query()->create([
            'name' => 'Terzo',
            'email' => 'tornato@example.com',
            'hiring_date' => '2020-01-01',
        ]);

        Livewire::test(ListEmployees::class)
            ->callTableAction('createUser', $employee)
            ->assertNotified();

        $this->assertSame($existing->id, $employee->refresh()->user_id);
        $this->assertFalse(User::withTrashed()->find($existing->id)->trashed());
    }
}
