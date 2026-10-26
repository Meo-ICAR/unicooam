<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Models\Client;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsulentiOrganigrammaTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_the_list_shows_only_consultants_not_the_clients_born_from_invoices(): void
    {
        $consultant = Client::query()->create(['name' => 'Studio Alfa', 'is_person' => false, 'is_company' => true]);
        $fromInvoice = Client::query()->create(['name' => 'Cliente da fattura', 'is_person' => true, 'is_company' => false]);

        Livewire::test(ListClients::class)
            ->assertCanSeeTableRecords([$consultant])
            ->assertCanNotSeeTableRecords([$fromInvoice]);
    }

    public function test_a_person_is_registered_as_a_consultant_with_tax_codes_and_employer(): void
    {
        $ancora = Client::query()->create(['name' => 'Ancora S.r.l.', 'is_person' => false, 'is_company' => true]);

        Livewire::test(CreateClient::class)
            ->fillForm(['name' => 'Gioia Quarto', 'is_person' => true, 'tax_code' => 'QRTGIO80A41F839X', 'vat_number' => '12345678901', 'employer_id' => $ancora->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $person = Client::query()->where('name', 'Gioia Quarto')->sole();
        $this->assertTrue($person->is_company);
        $this->assertSame($ancora->id, $person->employer->id);
        $this->assertSame('12345678901', $person->vat_number);

        Livewire::test(CreateClient::class)
            ->fillForm(['name' => 'Errata', 'vat_number' => '123'])
            ->call('create')
            ->assertHasFormErrors(['vat_number']);
    }
}
