<?php

namespace Tests\Feature;

use Unico\Core\Enums\DocumentStatus;
use Unico\Core\Enums\SyncStatus;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class UploadDocumentToSharePointTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function document(): Document
    {
        $company = Company::factory()->create();
        $employee = Employee::query()->create(['company_id' => $company->id, 'name' => 'Mario Rossi', 'email' => 'm@example.com']);
        $type = DocumentType::query()->create(['name' => 'Documento test', 'slug' => 'documento-test-'.Str::random(6), 'priority' => 1]);

        return Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'documentable_type' => Employee::class,
            'documentable_id' => (string) $employee->id,
            'document_type_id' => $type->id,
            'name' => 'Documento test',
            'status' => DocumentStatus::PENDING->value,
        ]);
    }

    private function configure(bool $withCredentials = true): void
    {
        config([
            'services.sharepoint.tenant_id' => $withCredentials ? 't' : null,
            'services.sharepoint.client_id' => 'c',
            'services.sharepoint.client_secret' => 's',
            'services.sharepoint.drive_id' => 'D',
            'services.sharepoint.upload_root' => 'Unicooam',
        ]);
        Cache::flush();
        Storage::fake('public');
    }

    public function test_adding_media_uploads_to_sharepoint_and_marks_the_document_synced(): void
    {
        $this->configure();
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'tok']),
            'graph.microsoft.com/*' => Http::response(['id' => 'item1', 'eTag' => '"e1"', 'webUrl' => 'https://sp/item1']),
        ]);
        $document = $this->document();

        $document->addMedia(UploadedFile::fake()->create('carta.pdf', 10))->toMediaCollection('documents');

        $document->refresh();
        $this->assertSame('synced', $document->sync_status);
        $this->assertSame('item1', $document->app_id);
        $this->assertSame('D', $document->app_drive_id);
        $this->assertSame('sharepoint', $document->source_app);
        $this->assertSame('https://sp/item1', $document->metadata['web_url']);
        Http::assertSent(fn ($r) => $r->method() === 'PUT' && str_contains($r->url(), 'root:/Unicooam/Mario%20Rossi/carta.pdf'));
    }

    public function test_failed_upload_marks_the_document_failed_and_keeps_the_local_file(): void
    {
        $this->configure();
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'tok']),
            'graph.microsoft.com/*' => Http::response(['error' => 'denied'], 403),
        ]);
        $document = $this->document();

        try {
            $document->addMedia(UploadedFile::fake()->create('carta.pdf', 10))->toMediaCollection('documents');
        } catch (\Throwable) {
            // con la coda sync l'eccezione risale: lo stato è comunque aggiornato da failed()
        }

        $document->refresh();
        $this->assertSame('failed', $document->sync_status);
        $this->assertNotEmpty($document->metadata['sync_error']);
        $this->assertCount(1, $document->getMedia('documents'));
    }

    public function test_without_credentials_the_document_stays_local(): void
    {
        $this->configure(withCredentials: false);
        Http::fake();
        $document = $this->document();

        $document->addMedia(UploadedFile::fake()->create('carta.pdf', 10))->toMediaCollection('documents');

        $this->assertSame(SyncStatus::LOCAL->value, $document->refresh()->sync_status);
        Http::assertNothingSent();
    }
}
