<?php

namespace App\Models;

use Unico\Core\Enums\DocumentStatus;
use App\ValueObjects\OamSemester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Unico\Core\Models\Document as CoreDocument;

class Document extends CoreDocument implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

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
