<?php

namespace Tests\Feature;

use App\Filament\Resources\DocumentSchedules\Pages\ManageDocumentSchedules;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSchedule;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\User;
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
                ->sortTable($column)
                ->assertSuccessful()
                ->sortTable($column, 'desc')
                ->assertSuccessful();
        }

        Livewire::test(ManageDocumentSchedules::class)
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
            ->assertTableColumnExists('document_name', fn ($column) => $column->getUrl() === route('documents.download', $withFile->document_id), $withFile)
            ->assertTableColumnExists('document_name', fn ($column) => $column->getUrl() === route('documents.download', $withUrl->document_id), $withUrl)
            ->assertTableColumnExists('document_name', fn ($column) => $column->getUrl() === null, $without);
    }
}
