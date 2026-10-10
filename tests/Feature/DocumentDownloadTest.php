<?php

namespace Tests\Feature;

use Unico\Core\Enums\DocumentStatus;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function document(array $attributes = []): Document
    {
        $company = Company::factory()->create();

        return Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'documentable_type' => 'company',
            'documentable_id' => $company->id,
            'name' => 'Allegato',
            'status' => DocumentStatus::APPROVED->value,
            ...$attributes,
        ]);
    }

    public function test_it_serves_the_local_file_when_present(): void
    {
        Storage::fake('public');
        $document = $this->document(['document_url' => 'https://remote.example/file.pdf']);
        $document->addMedia(UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->toMediaCollection('documents');
        $this->actingAs(User::factory()->create());

        $this->get(route('documents.download', $document))
            ->assertOk()
            ->assertDownload('doc.pdf');
    }

    public function test_it_redirects_to_document_url_when_there_is_no_local_file(): void
    {
        $document = $this->document(['document_url' => 'https://remote.example/file.pdf']);
        $this->actingAs(User::factory()->create());

        $this->get(route('documents.download', $document))->assertRedirect('https://remote.example/file.pdf');
    }

    public function test_it_falls_back_to_metadata_web_url_when_document_url_is_empty(): void
    {
        $document = $this->document(['metadata' => ['web_url' => 'https://sp.example/item1']]);
        $this->actingAs(User::factory()->create());

        $this->get(route('documents.download', $document))->assertRedirect('https://sp.example/item1');
    }

    public function test_it_prepends_https_to_urls_without_a_scheme(): void
    {
        $document = $this->document(['document_url' => 'remote.example/file.pdf']);
        $this->actingAs(User::factory()->create());

        $this->get(route('documents.download', $document))->assertRedirect('https://remote.example/file.pdf');
    }

    public function test_it_returns_404_without_local_file_or_url(): void
    {
        $document = $this->document();
        $this->actingAs(User::factory()->create());

        $this->get(route('documents.download', $document))->assertNotFound();
    }
}
