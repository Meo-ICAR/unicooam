<?php

namespace App\Models;

use App\Enums\AuditStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audit extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * I campi che possono essere assegnati massivamente.
     *
     * @var array<int, string>
     */
    protected $connection = 'mysql';

    protected $fillable = [
        'company_id',
        'auditable_type',
        'auditable_id',
        'protocol_number',
        'origin_type',
        'execution_method',
        'authority_type',
        'authority_name',
        'title',
        'scope',
        'scheduled_at',
        'executed_at',
        'followup_date',
        'status',
        'outcome',
        'summary',
        'auditor_notes',
    ];

    /**
     * I cast nativi per gli attributi del database.
     * Sfruttiamo i PHP Enums per automatizzare la logica in Filament.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'date',
        'executed_at' => 'date',
        'followup_date' => 'date',
        // Cast degli Enum (Assicurati di creare questi file in App\Enums)
        'status' => AuditStatus::class,
        // Puoi creare enum dedicati anche per gli altri campi stringa se preferisci:
        // 'origin_type' => \App\Enums\AuditOrigin::class,
        // 'execution_method' => \App\Enums\AuditExecution::class,
        // 'outcome' => \App\Enums\AuditOutcome::class,
    ];

    /**
     * Relazione con l'azienda principale (Tenant / Proprietaria del record).
     * Nota: nella migrazione è foreignUuid, Laravel gestirà l'integrità automaticamente.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relazione Polimorfica (auditable_type + auditable_id).
     * Rappresenta il soggetto controllato (es. ReteCommerciale, BancaMandante, OrganismoVigilanza, Client).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * SCOPE: Filtra gli audit che richiedono un follow-up urgente.
     */
    public function scopeNeedsFollowup($query)
    {
        return $query
            ->where('status', AuditStatus::PendingFollowup)
            ->where('followup_date', '<=', now()->addDays(7));
    }

    /**
     * HELPER: Verifica se l'audit è stato eseguito in ritardo rispetto alla pianificazione.
     */
    public function isDelayed(): bool
    {
        if ($this->executed_at && $this->scheduled_at) {
            return $this->executed_at->isAfter($this->scheduled_at);
        }

        return $this->scheduled_at->isPast() && !$this->executed_at;
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class, 'audit_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
