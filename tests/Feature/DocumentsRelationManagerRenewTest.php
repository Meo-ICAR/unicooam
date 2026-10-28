<?php

namespace Tests\Feature;

use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;
use Unico\Core\Filament\RelationManagers\DocumentsRelationManager;

class DocumentsRelationManagerRenewTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function document(Employee $employee, string $name, bool $monitored): Document
    {
        $type = DocumentType::query()->create(['name' => 'Tipo '.Str::random(6), 'slug' => 'tipo-'.Str::random(6), 'priority' => 1, 'is_monitored' => $monitored]);

        return Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $employee->company_id,
            'documentable_type' => 'employee',
            'documentable_id' => (string) $employee->id,
            'document_type_id' => $type->id,
            'name' => $name,
            'status' => 'approvato',
            'is_monitored' => $monitored,
            'emitted_at' => now()->subYear()->toDateString(),
        ]);
    }

    public function test_the_core_renew_action_clones_the_document_keeping_the_old_one_as_previous_version(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $employee = Employee::query()->create(['company_id' => Company::factory()->create()->id, 'name' => 'ROSSI MARIO']);
        $monitored = $this->document($employee, 'Polizza RC', true);

        $page = Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $employee, 'pageClass' => EditEmployee::class]);

        $page->callAction([TestAction::make('edit')->table($monitored), TestAction::make('renew')], ['emitted_at' => now()->toDateString()])
            ->assertHasNoFormErrors();

        $old = $monitored->fresh();
        $new = Document::query()->where('name', 'Polizza RC')->where('id', '!=', $old->id)->sole();

        $this->assertSame($new->id, $old->replaced_by_id, 'Il documento attuale resta come versione precedente della copia');
        $this->assertSame((string) $employee->id, (string) $new->documentable_id);
        $this->assertTrue((bool) $new->is_monitored);
    }

    public function test_the_current_semester_filter_is_available_but_not_applied_by_default(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $employee = Employee::query()->create(['company_id' => Company::factory()->create()->id, 'name' => 'ROSSI MARIO']);

        $page = Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $employee, 'pageClass' => EditEmployee::class]);

        $this->assertNotNull($page->instance()->getTable()->getFilter('semestre_attuale'));
        $this->assertEmpty($page->get('tableFilters.semestre_attuale.isActive'), 'Di default non si filtra sul semestre in corso');
    }
}
