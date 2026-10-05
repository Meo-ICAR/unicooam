<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentRenewalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_renew_creates_a_new_document_and_marks_the_previous_one_as_expired(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $employee = Employee::query()->create([
            'company_id' => $company->id,
            'name' => 'Mario Rossi',
            'email' => 'dipendente@example.com',
        ]);

        $documentType = DocumentType::query()->create([
            'name' => 'Iscrizione OAM',
            'slug' => 'iscrizione-oam-test',
            'priority' => 1,
            'is_monitored' => true,
            'duration' => 1,
            'duration_unit' => 'years',
        ]);

        $document = Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'documentable_type' => Employee::class,
            'documentable_id' => (string) $employee->id,
            'document_type_id' => $documentType->id,
            'name' => 'Iscrizione OAM',
            'status' => 'approved',
            'is_monitored' => true,
            'emitted_at' => now()->subYear(),
        ]);

        $document->addMedia(UploadedFile::fake()->create('vecchio.pdf', 10, 'application/pdf'))
            ->toMediaCollection('documents');

        $newEmittedAt = now()->toDateString();
        $newDocument = $document->renew($newEmittedAt);
        $newDocument->addMedia(UploadedFile::fake()->create('nuovo.pdf', 10, 'application/pdf'))
            ->toMediaCollection('documents');

        $document->refresh();

        $this->assertNotSame($document->id, $newDocument->id);
        $this->assertSame($newEmittedAt, $newDocument->emitted_at->toDateString());
        $this->assertSame('approved', $newDocument->status);
        $this->assertTrue($newDocument->hasMedia('documents'));
        $this->assertSame('nuovo.pdf', $newDocument->getFirstMedia('documents')->file_name);

        $this->assertSame('expired', $document->status);
        $this->assertSame($newDocument->id, $document->metadata['renewed_to_uuid']);
        $this->assertTrue($document->hasMedia('documents'));
        $this->assertSame('vecchio.pdf', $document->getFirstMedia('documents')->file_name);
    }

    public function test_renew_action_attaches_uploaded_attachment_from_temporary_disk_and_cleans_it_up(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $employee = Employee::query()->create([
            'company_id' => $company->id,
            'name' => 'Mario Rossi',
            'email' => 'dipendente@example.com',
        ]);

        $documentType = DocumentType::query()->create([
            'name' => 'Iscrizione OAM',
            'slug' => 'iscrizione-oam-test-action',
            'priority' => 1,
            'is_monitored' => true,
            'duration' => 1,
            'duration_unit' => 'years',
        ]);

        $document = Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'documentable_type' => Employee::class,
            'documentable_id' => (string) $employee->id,
            'document_type_id' => $documentType->id,
            'name' => 'Iscrizione OAM',
            'status' => 'approved',
            'is_monitored' => true,
            'emitted_at' => now()->subYear(),
        ]);

        // Simula il file caricato dal FileUpload dell'azione "renew" prima del salvataggio.
        $temporaryPath = UploadedFile::fake()
            ->create('nuovo.pdf', 10, 'application/pdf')
            ->store('document-renewals', 'public');

        $newEmittedAt = now()->toDateString();

        // Stessa logica eseguita dall'azione "renew" in DocumentsTable e DocumentsRelationManager.
        $newDocument = $document->renew($newEmittedAt);
        $newDocument->addMediaFromDisk($temporaryPath, 'public')
            ->toMediaCollection('documents');
        Storage::disk('public')->delete($temporaryPath);

        $this->assertTrue($newDocument->hasMedia('documents'));
        $this->assertSame('application/pdf', $newDocument->getFirstMedia('documents')->mime_type);
        Storage::disk('public')->assertMissing($temporaryPath);

        $document->refresh();
        $this->assertSame('expired', $document->status);
    }
}
