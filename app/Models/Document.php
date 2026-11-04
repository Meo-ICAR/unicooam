<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUserCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Unico\Core\Models\Document as CoreDocument;

class Document extends CoreDocument
{
    use HasFactory, SoftDeletes;
    use ScopedToUserCompany;

    /**
     * Relazione: Tipo di documento
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);  // Presume l'esistenza del model DocumentType
    }

    /**
     * Relazione: Tenant proprietario
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // --- Audit & User Relations ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(DocumentReminder::class);
    }

    public function renewedBy($document_id): string
    {
        $nomeDocumento = $this->name;
        $renewedById = $this->documentType()?->renewed_by_id;
        if ($renewedById) {
            $nomeDocumento = DocumentType::find($renewedById)->first()->name;
        }

        return $nomeDocumento;
    }
}
