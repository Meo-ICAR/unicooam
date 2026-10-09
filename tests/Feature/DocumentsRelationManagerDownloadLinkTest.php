<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\RelationManagers\DocumentsRelationManager;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentsRelationManagerDownloadLinkTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function document(Company $company, array $attributes = []): Document
    {
        return Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'documentable_type' => 'company',
            'documentable_id' => $company->id,
            'name' => 'Allegato',
            'status' => 'approvato',
            ...$attributes,
        ]);
    }

    public function test_download_url_exists_only_with_an_attachment_or_a_url(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $withFile = $this->document($company);
        $withFile->addMedia(UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->toMediaCollection('documents');

        $this->assertSame(route('documents.download', $withFile), $withFile->refresh()->download_url);
        $this->assertSame(
            route('documents.download', $withUrl = $this->document($company, ['metadata' => ['web_url' => 'https://sp/x']])),
            $withUrl->download_url,
        );
        $this->assertNull($this->document($company)->download_url);
    }

    public function test_document_name_in_the_relation_manager_links_to_the_download(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $withFile = $this->document($company, ['name' => 'Con file']);
        $withFile->addMedia(UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->toMediaCollection('documents');
        $without = $this->document($company, ['name' => 'Senza file']);
        $this->actingAs(User::factory()->create());

        Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $company, 'pageClass' => EditCompany::class])
            ->filterTable('semestre_attuale', false)
            ->filterTable('is_expired', null)
            ->assertCanSeeTableRecords([$withFile, $without])
            ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getUrl() === route('documents.download', $withFile), $withFile)
            ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getUrl() === null, $without);
    }
}
