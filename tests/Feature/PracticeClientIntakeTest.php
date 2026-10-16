<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Pratica;
use App\Models\Task;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Unico\Core\Documents\PracticeDocumentIntake;
use Unico\Core\Models\Client;
use Unico\Core\Models\DocumentRequirement;
use Unico\Core\Models\PraticaClient;

/**
 * I documenti che spettano ai client della pratica, caricati sulla pratica, vanno al client avente diritto.
 */
class PracticeClientIntakeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_typed_upload_goes_to_the_first_entitled_client_without_a_file(): void
    {
        Storage::fake('public');

        $task = Task::create(['name' => 'Check list', 'taskable' => 'pratica', 'is_active' => true]);
        $identita = DocumentType::create(['name' => 'Carta identità', 'code' => 'CI', 'is_person' => true]);
        DocumentRequirement::create(['task_id' => $task->id, 'document_type_id' => $identita->id, 'documentable' => 'client', 'client_roles' => ['richiedente', 'coobbligato']]);

        $pratica = Pratica::create(['codice_pratica' => 'P-I1', 'upload_at' => now()]);
        $richiedente = Client::create(['name' => 'Rossi', 'is_person' => true]);
        $coobbligato = Client::create(['name' => 'Verdi', 'is_person' => true]);
        PraticaClient::create(['pratica_id' => $pratica->getKey(), 'client_id' => $richiedente->id, 'role' => 'richiedente']);
        PraticaClient::create(['pratica_id' => $pratica->getKey(), 'client_id' => $coobbligato->id, 'role' => 'coobbligato']);
        $task->generateDocumentsFor($pratica);

        $intake = app(PracticeDocumentIntake::class);
        $types = collect([$identita]);

        $this->assertSame([$identita->id => false], $intake->received($pratica, $types));

        $first = $intake->storeTyped($pratica, UploadedFile::fake()->create('id-1.pdf', 10, 'application/pdf'), $identita->id, $types);
        $this->assertSame('client', $first->documentable_type);
        $this->assertSame((string) $richiedente->id, (string) $first->documentable_id);
        $this->assertSame([$identita->id => false], $intake->received($pratica, $types), 'manca ancora il coobbligato');

        $second = $intake->storeTyped($pratica, UploadedFile::fake()->create('id-2.pdf', 10, 'application/pdf'), $identita->id, $types);
        $this->assertSame((string) $coobbligato->id, (string) $second->documentable_id);
        $this->assertSame([$identita->id => true], $intake->received($pratica, $types));
        $this->assertSame(2, Document::query()->where('document_type_id', $identita->id)->count());
    }
}
