<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\RelationManagers\DocumentsRelationManager;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentBundleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_create_bundle_copies_clones_the_document_with_each_type_emission_date_and_attachment(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $primaryType = $this->createDocumentType('Visura', duration: 6, unit: 'months');
        $durcType = $this->createDocumentType('DURC', duration: 4, unit: 'months');
        $insuranceType = $this->createDocumentType('Polizza RC', duration: 1, unit: 'years');

        $primary = Document::query()->create([
            'company_id' => $company->id,
            'documentable_type' => Company::class,
            'documentable_id' => (string) $company->id,
            'document_type_id' => $primaryType->id,
            'name' => $primaryType->name,
            'is_monitored' => true,
            'emitted_at' => '2026-01-10',
        ]);
        $primary->addMedia(UploadedFile::fake()->create('plico.pdf', 10, 'application/pdf'))
            ->toMediaCollection('documents');

        $copies = $primary->createBundleCopies([
            ['document_type_id' => $durcType->id, 'emitted_at' => '2026-02-01', 'docnumber' => 'D-1'],
            ['document_type_id' => $insuranceType->id, 'emitted_at' => '2026-03-15', 'docnumber' => null],
        ]);

        $this->assertCount(2, $copies);
        $this->assertSame(3, Document::query()->count());

        [$durc, $insurance] = [$copies[0]->refresh(), $copies[1]->refresh()];

        $this->assertSame('DURC', $durc->name);
        $this->assertSame('D-1', $durc->docnumber);
        $this->assertSame('2026-06-01', $durc->expires_at->toDateString());
        $this->assertSame('2027-03-15', $insurance->expires_at->toDateString());

        foreach ([$durc, $insurance] as $copy) {
            $this->assertSame($company->id, $copy->company_id);
            $this->assertSame(Company::class, $copy->documentable_type);
            $this->assertSame((string) $company->id, (string) $copy->documentable_id);
            $this->assertTrue($copy->is_monitored);
            $this->assertCount(1, $copy->getMedia('documents'));
            $this->assertSame('plico.pdf', $copy->getFirstMedia('documents')->file_name);
        }

        $primary->refresh();
        $this->assertNotEmpty($primary->metadata['bundle_id']);
        $this->assertSame($primary->metadata['bundle_id'], $durc->metadata['bundle_id']);
        $this->assertSame($primary->metadata['bundle_id'], $insurance->metadata['bundle_id']);
        $this->assertCount(1, $primary->getMedia('documents'));
    }

    public function test_create_bundle_copies_is_atomic_when_a_row_has_an_unknown_document_type(): void
    {
        $company = Company::factory()->create();
        $type = $this->createDocumentType('DURC', duration: 4, unit: 'months');

        $primary = Document::query()->create([
            'company_id' => $company->id,
            'documentable_type' => Company::class,
            'documentable_id' => (string) $company->id,
            'document_type_id' => $type->id,
            'name' => 'DURC',
        ]);

        try {
            $primary->createBundleCopies([
                ['document_type_id' => $type->id, 'emitted_at' => '2026-02-01'],
                ['document_type_id' => 999999, 'emitted_at' => '2026-02-01'],
            ]);
            $this->fail('Expected a ModelNotFoundException.');
        } catch (ModelNotFoundException) {
            // atteso
        }

        $this->assertSame(1, Document::query()->count());
        $this->assertNull($primary->refresh()->metadata['bundle_id'] ?? null);
    }

    public function test_relation_manager_create_action_creates_one_document_per_bundle_row(): void
    {
        $this->actingAs(User::factory()->create());

        $company = Company::factory()->create();
        $primaryType = $this->createDocumentType('Visura', duration: 6, unit: 'months');
        $durcType = $this->createDocumentType('DURC', duration: 4, unit: 'months');

        Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $company,
            'pageClass' => EditCompany::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'document_type_id' => $primaryType->id,
                'name' => 'Visura',
                'emitted_at' => '2026-01-10',
                'is_monitored' => true,
                'plico' => [
                    ['document_type_id' => $durcType->id, 'emitted_at' => '2026-02-01', 'docnumber' => null],
                ],
            ])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $documents = Document::query()->orderBy('name')->get();

        $this->assertCount(2, $documents);
        $this->assertSame(['DURC', 'Visura'], $documents->pluck('name')->all());
        $this->assertSame('2026-06-01', $documents->firstWhere('name', 'DURC')->expires_at->toDateString());
        $this->assertSame(
            $documents[0]->metadata['bundle_id'],
            $documents[1]->metadata['bundle_id'],
        );
    }

    public function test_relation_manager_create_action_without_bundle_creates_a_single_document(): void
    {
        $this->actingAs(User::factory()->create());

        $company = Company::factory()->create();
        $type = $this->createDocumentType('Visura', duration: 6, unit: 'months');

        Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $company,
            'pageClass' => EditCompany::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'document_type_id' => $type->id,
                'name' => 'Visura',
                'emitted_at' => '2026-01-10',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(1, Document::query()->count());
        $this->assertNull(Document::query()->first()->metadata['bundle_id'] ?? null);
    }

    private function createDocumentType(string $name, int $duration, string $unit): DocumentType
    {
        return DocumentType::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.uniqid())->toString(),
            'priority' => 1,
            'is_monitored' => true,
            'duration' => $duration,
            'duration_unit' => $unit,
        ]);
    }
}
