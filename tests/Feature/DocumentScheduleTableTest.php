<?php

namespace Tests\Feature;

use App\Filament\Resources\DocumentSchedules\Pages\ManageDocumentSchedules;
use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSchedule;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentScheduleTableTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function schedule(string $entity, string $document, string $expiresAt, ?string $email = null): DocumentSchedule
    {
        $company = Company::factory()->create();
        $employee = Employee::query()->create(['company_id' => $company->id, 'name' => $entity, 'email' => $email ?? Str::random(6).'@example.com']);

        $type = DocumentType::query()->create(['name' => 'Tipo '.Str::random(6), 'slug' => 'tipo-'.Str::random(6), 'priority' => 1]);
        $doc = Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'documentable_type' => 'employee',
            'documentable_id' => (string) $employee->id,
            'document_type_id' => $type->id,
            'name' => $document,
            'status' => 'approvato',
        ]);

        return DocumentSchedule::query()->create([
            'document_id' => $doc->id,
            'documentable_group_key' => 'employee|'.$employee->id,
            'document_name' => $document,
            'document_type_name' => 'Tipo',
            'entity_name' => $entity,
            'documentable_type' => 'employee',
            'documentable_id' => (string) $employee->id,
            'expires_at' => $expiresAt,
            'days_until_expiry' => 10,
            'status' => 'approvato',
            'reminders_count' => 0,
        ]);
    }

    public function test_table_can_be_sorted_and_searched(): void
    {
        $rossi = $this->schedule('ROSSI MARIO', 'Casellario', '2026-11-01', 'rossi@example.com');
        $bianchi = $this->schedule('BIANCHI LUCA', 'Polizza', '2026-12-01');
        $this->actingAs(User::factory()->create());

        foreach (['expires_at', 'entity_name', 'document_name', 'days_until_expiry', 'reminders_count', 'last_sent_at'] as $column) {
            Livewire::test(ManageDocumentSchedules::class)
                ->removeTableFilter('documentable_type')
                ->sortTable($column)
                ->assertSuccessful()
                ->sortTable($column, 'desc')
                ->assertSuccessful();
        }

        Livewire::test(ManageDocumentSchedules::class)
            ->removeTableFilter('documentable_type')
            ->searchTable('ROSSI')
            ->assertCanSeeTableRecords([$rossi])
            ->assertCanNotSeeTableRecords([$bianchi])
            ->searchTable('Polizza')
            ->assertCanSeeTableRecords([$bianchi])
            ->assertCanNotSeeTableRecords([$rossi]);
    }

    public function test_table_can_be_searched_by_email(): void
    {
        $rossi = $this->schedule('ROSSI MARIO', 'Casellario', '2026-11-01', 'rossi@example.com');
        $bianchi = $this->schedule('BIANCHI LUCA', 'Polizza', '2026-12-01', 'bianchi@example.com');
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageDocumentSchedules::class)
            ->removeTableFilter('documentable_type')
            ->searchTable('rossi@example.com')
            ->assertCanSeeTableRecords([$rossi])
            ->assertCanNotSeeTableRecords([$bianchi]);
    }

    public function test_email_search_does_not_break_with_rows_of_other_databases(): void
    {
        $rossi = $this->schedule('ROSSI MARIO', 'Casellario', '2026-11-01', 'rossi@example.com');
        $fornitoreRow = $this->schedule('VERDI ANNA', 'Polizza', '2026-12-01');
        $fornitoreRow->forceFill(['documentable_type' => 'fornitore', 'documentable_id' => (string) Str::uuid()])->save();
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageDocumentSchedules::class)
            ->removeTableFilter('documentable_type')
            ->searchTable('rossi@example.com')
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$rossi])
            ->assertCanNotSeeTableRecords([$fornitoreRow]);
    }

    public function test_document_name_links_to_the_download_only_when_an_attachment_exists(): void
    {
        Storage::fake('public');
        $withFile = $this->schedule('ROSSI MARIO', 'Con allegato', '2026-11-01');
        $withFile->document->addMedia(UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->toMediaCollection('documents');
        $withUrl = $this->schedule('BIANCHI LUCA', 'Con url', '2026-11-02');
        $withUrl->document->forceFill(['document_url' => 'https://remote.example/f.pdf'])->saveQuietly();
        $without = $this->schedule('VERDI ANNA', 'Senza allegato', '2026-11-03');
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageDocumentSchedules::class)
            ->removeTableFilter('documentable_type')
            ->assertTableColumnExists('document_name', fn ($column) => $column->getUrl() === route('documents.download', $withFile->document_id), $withFile)
            ->assertTableColumnExists('document_name', fn ($column) => $column->getUrl() === route('documents.download', $withUrl->document_id), $withUrl)
            ->assertTableColumnExists('document_name', fn ($column) => $column->getUrl() === null, $without);
    }

    public function test_the_recipients_filter_is_not_applied_by_default_and_can_narrow_to_the_producer(): void
    {
        $produttore = $this->schedule('ZZ PRODUTTORE', 'Doc produttore', now()->addDays(5)->toDateString());
        $produttore->forceFill(['documentable_type' => 'fornitore', 'documentable_id' => (string) Str::uuid()])->save();
        $this->schedule('ZZ DIPENDENTE', 'Doc dipendente', now()->addDays(6)->toDateString());

        $this->actingAs(User::factory()->create());

        Livewire::test(ManageDocumentSchedules::class)
            ->assertCountTableRecords(2)
            ->assertSee('ZZ DIPENDENTE')
            ->filterTable('documentable_type', 'fornitore')
            ->assertCountTableRecords(1)
            ->assertSee('ZZ PRODUTTORE')
            ->assertDontSee('ZZ DIPENDENTE');
    }

    public function test_the_subject_opens_its_registry_card_when_it_has_one(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $employeeRow = $this->schedule('ROSSI MARIO', 'Casellario', '2026-11-01');
        $consultant = Client::query()->create(['name' => 'Studio Alfa', 'is_person' => false, 'is_company' => true]);
        $plainClient = Client::query()->create(['name' => 'Cliente da fattura', 'is_person' => true, 'is_company' => false]);

        $row = fn (string $type, int|string $id) => $employeeRow->replicate()->forceFill(['documentable_type' => $type, 'documentable_id' => (string) $id]);
        $column = Livewire::test(ManageDocumentSchedules::class)->instance()->getTable()->getColumn('entity_name');
        $urlOf = fn (DocumentSchedule $record): ?string => $column->record($record)->getUrl();

        $this->assertStringEndsWith('/employees/'.$employeeRow->documentable_id.'/edit', (string) $urlOf($employeeRow));
        $this->assertStringEndsWith('/clients/'.$consultant->id.'/edit', (string) $urlOf($row('client', $consultant->id)));
        $this->assertNull($urlOf($row('client', $plainClient->id)), 'Un client che non è consulente non ha scheda in questa app');
        $this->assertNull($urlOf($row('pratica', 1)), 'Le pratiche non hanno una scheda');
    }

    public function test_renew_shows_document_and_owner_then_clones_the_document_keeping_the_old_one_as_previous_version(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $row = $this->schedule('ROSSI MARIO', 'Polizza RC', now()->addDays(5)->toDateString());
        $row->document->update(['emitted_at' => now()->subYear()->toDateString(), 'is_monitored' => true]);
        $old = $row->document;

        $page = Livewire::test(ManageDocumentSchedules::class)->removeTableFilters()->mountAction(TestAction::make('renew')->table($row));
        $modal = $page->instance()->getMountedAction();

        $this->assertSame('Rinnova: Polizza RC', $modal->getModalHeading());
        $this->assertStringContainsString('Proprietario: ROSSI MARIO', (string) $modal->getModalDescription());

        $page->callMountedAction(['emitted_at' => now()->toDateString()])->assertHasNoFormErrors();

        $new = Document::query()->where('replaced_by_id', null)->where('name', 'Polizza RC')->where('id', '!=', $old->id)->sole();
        $old->refresh();

        $this->assertSame($new->id, $old->replaced_by_id, 'Il documento attuale è la versione precedente della copia');
        $this->assertSame('scaduto', $old->status instanceof \BackedEnum ? $old->status->value : $old->status);
        $this->assertSame($old->documentable_id, $new->documentable_id);
        $this->assertSame($old->document_type_id, $new->document_type_id);
        $this->assertSame($new->id, $row->fresh()->document_id, 'La riga dello scadenziario punta alla copia');
    }
}
