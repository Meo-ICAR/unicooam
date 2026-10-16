<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Unico\Core\Documents\PlicoArchive;
use Unico\Core\Filament\RelationManagers\DocumentsRelationManager;
use Unico\Core\Models\DocumentRequirement;
use ZipArchive;

/**
 * I documenti di un plico si generano per il record e si scaricano tutti insieme in uno ZIP.
 */
class PlicoDownloadTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_generate_then_download_all_the_documents_of_a_plico(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $company = Company::factory()->create();
        $task = Task::create(['name' => 'Plico azienda', 'taskable' => 'company', 'is_active' => true]);
        $visura = DocumentType::create(['name' => 'Visura', 'code' => 'VIS']);
        $durc = DocumentType::create(['name' => 'DURC', 'code' => 'DURC']);
        DocumentRequirement::create(['task_id' => $task->id, 'document_type_id' => $visura->id, 'sort_order' => 1]);
        DocumentRequirement::create(['task_id' => $task->id, 'document_type_id' => $durc->id, 'sort_order' => 2]);

        $relationManager = fn () => Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $company, 'pageClass' => EditCompany::class]);

        $relationManager()->callAction(TestAction::make('generaPlico')->table(), ['plichi' => [$task->id]])->assertNotified();

        $documents = Document::query()->where('documentable_id', (string) $company->id)->get();
        $this->assertCount(2, $documents);

        foreach ($documents as $document) {
            $document->addMedia(UploadedFile::fake()->create($document->name.'.pdf', 10, 'application/pdf'))->toMediaCollection('documents');
        }

        $relationManager()->callAction(TestAction::make('scaricaPlico')->table(), ['plichi' => [$task->id]])->assertFileDownloaded();

        $zipPath = (new PlicoArchive)->zip([$task], $company);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath));
        $this->assertSame(3, $zip->numFiles);
        $this->assertNotFalse($zip->locateName('indice.txt'));
        $zip->close();
        unlink($zipPath);
    }
}
