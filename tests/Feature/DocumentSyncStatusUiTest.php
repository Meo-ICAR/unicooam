<?php

namespace Tests\Feature;

use App\Enums\SyncStatus;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Jobs\UploadDocumentToSharePoint;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentSyncStatusUiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function document(string $syncStatus, array $metadata = [], bool $withMedia = false): Document
    {
        $company = Company::factory()->create();
        $employee = Employee::query()->create(['company_id' => $company->id, 'name' => 'Mario Rossi', 'email' => 'm@example.com']);
        $type = DocumentType::query()->create(['name' => 'Documento test', 'slug' => 'documento-test-'.Str::random(6), 'priority' => 1]);

        $document = Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'documentable_type' => 'employee',
            'documentable_id' => (string) $employee->id,
            'document_type_id' => $type->id,
            'name' => 'Documento test',
            'status' => 'pending',
            'sync_status' => $syncStatus,
            'metadata' => $metadata,
        ]);

        if ($withMedia) {
            $document->addMedia(UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->toMediaCollection('documents');
            Document::query()->whereKey($document->id)->update(['sync_status' => $syncStatus]);
        }

        return $document->refresh();
    }

    private function listPage()
    {
        $this->actingAs(User::factory()->create());

        return Livewire::test(ListDocuments::class)
            ->filterTable('semestre_attuale', false)
            ->filterTable('documentable_type', 'employee');
    }

    public function test_list_shows_the_sync_status_badge(): void
    {
        $document = $this->document(SyncStatus::SYNCED->value, ['web_url' => 'https://sp/item1']);

        $this->listPage()
            ->assertCanSeeTableRecords([$document])
            ->assertTableColumnFormattedStateSet('sync_status', SyncStatus::SYNCED->getLabel(), $document);
    }

    public function test_failed_documents_expose_the_sync_error_as_tooltip(): void
    {
        $document = $this->document(SyncStatus::FAILED->value, ['sync_error' => 'HTTP 403']);

        $this->listPage()->assertTableColumnExists('sync_status', fn (TextColumn $column): bool => $column->getTooltip() === 'HTTP 403', $document);
    }

    public function test_open_on_sharepoint_is_visible_only_for_synced_documents_with_a_url(): void
    {
        $synced = $this->document(SyncStatus::SYNCED->value, ['web_url' => 'https://sp/item1']);
        $local = $this->document(SyncStatus::LOCAL->value);

        $this->listPage()
            ->assertActionVisible(TestAction::make('openOnSharePoint')->table($synced))
            ->assertActionHidden(TestAction::make('openOnSharePoint')->table($local));
    }

    public function test_retry_action_requeues_a_failed_upload(): void
    {
        Storage::fake('public');
        config([
            'services.sharepoint.tenant_id' => 't',
            'services.sharepoint.client_id' => 'c',
            'services.sharepoint.client_secret' => 's',
            'services.sharepoint.drive_id' => 'D',
        ]);
        Bus::fake();
        $document = $this->document(SyncStatus::FAILED->value, ['sync_error' => 'boom'], withMedia: true);
        Bus::fake();

        $this->listPage()
            ->callAction(TestAction::make('retrySharePointUpload')->table($document))
            ->assertNotified('Upload rimesso in coda');

        Bus::assertDispatched(UploadDocumentToSharePoint::class, fn ($job) => $job->documentId === $document->id);
    }

    public function test_retry_action_is_hidden_for_synced_documents_and_when_sharepoint_is_not_configured(): void
    {
        Storage::fake('public');
        Bus::fake();
        config(['services.sharepoint.drive_id' => null]);
        $failedUnconfigured = $this->document(SyncStatus::FAILED->value, withMedia: true);

        $this->listPage()->assertActionHidden(TestAction::make('retrySharePointUpload')->table($failedUnconfigured));

        config([
            'services.sharepoint.tenant_id' => 't',
            'services.sharepoint.client_id' => 'c',
            'services.sharepoint.client_secret' => 's',
            'services.sharepoint.drive_id' => 'D',
        ]);
        $synced = $this->document(SyncStatus::SYNCED->value, ['web_url' => 'https://sp/x'], withMedia: true);

        $this->listPage()->assertActionHidden(TestAction::make('retrySharePointUpload')->table($synced));
    }
}
