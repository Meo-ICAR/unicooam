<?php

namespace App\Models;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Pivot;

class Task extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $orderBy = 'name';
    protected $orderDirection = 'asc';
    protected $fillable = ['name', 'description', 'taskable'];

    /**
     * I tipi di documento associati a questo Task
     */
    public function documentTypes()
    {
        return $this
            ->belongsToMany(DocumentType::class, 'task_document_types')
            ->using(TaskDocumentType::class)  // <-- Usa il nuovo modello Pivot
            ->withPivot('slug', 'is_required')
            ->withTimestamps();
    }

    /**
     * Crea la documentazione mancante per questo specifico task.
     *
     * @param int $companyId ID dell'azienda principale
     * @param int $documentableId ID del record di destinazione (ID Azienda o ID Fornitore)
     * @return int Numero di documenti creati
     */
    public function createDocumentation(int $companyId, int $documentableId): int
    {
        $createdCount = 0;

        // Clicliamo sui documentTypes già caricati in memoria
        foreach ($this->documentTypes as $documentType) {
            // 1. Prendi tutti i campi dal template ESCLUDENDO ID e Timestamps
            $templateData = $documentType->except(['id', 'created_at', 'updated_at', 'deleted_at']);

            // 2. Aggiungi/Sovrascrivi i campi specifici per la creazione
            $creationData = array_merge($templateData, [
                'status' => 'pending',
            ]);

            // 3. Esegui il firstOrCreate
            $document = Document::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'documentable_type' => $this->taskable,
                    'documentable_id' => $documentableId,
                    'document_type_id' => $documentType->id,
                ],
                $creationData
            );

            if ($document->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        return $createdCount;
    }
}
