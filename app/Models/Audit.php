<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'auditable_type',
        'auditable_id',
        'direction',
        'authority_type',
        'authority_name',
        'title',
        'scope',
        'audit_date',
        'followup_date',
        'status',
        'summary',
        'auditor_notes',
    ];

    protected function casts(): array
    {
        return [
            'audit_date'    => 'date',
            'followup_date' => 'date',
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
            'deleted_at'    => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class);
    }

    public function openFindings(): HasMany
    {
        return $this->hasMany(AuditFinding::class)->whereNotIn('status', ['resolved', 'accepted_risk', 'closed']);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePendingFollowup($query)
    {
        return $query->where('status', 'pending_followup');
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getDirectionLabelAttribute(): string
    {
        return self::getDirectionOptions()[$this->direction] ?? $this->direction;
    }

    public function getDirectionColorAttribute(): string
    {
        return match ($this->direction) {
            'outgoing' => 'info',
            'incoming' => 'warning',
            default    => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'planned'          => 'gray',
            'in_progress'      => 'warning',
            'completed'        => 'success',
            'pending_followup' => 'danger',
            default            => 'gray',
        };
    }

    public function getAuthorityTypeLabelAttribute(): string
    {
        return self::getAuthorityTypeOptions()[$this->authority_type] ?? ($this->authority_type ?? '—');
    }

    public function getHasOpenFindingsAttribute(): bool
    {
        return $this->openFindings()->exists();
    }

    // ── Options ──────────────────────────────────────────────────────────────

    public static function getDirectionOptions(): array
    {
        return [
            'outgoing' => 'Audit su responsabile del trattamento (outgoing)',
            'incoming' => 'Audit ricevuto da autorità / cliente (incoming)',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'planned'          => 'Pianificato',
            'in_progress'      => 'In corso',
            'completed'        => 'Completato',
            'pending_followup' => 'In attesa follow-up',
        ];
    }

    public static function getAuthorityTypeOptions(): array
    {
        return [
            'garante'      => 'Garante Privacy (GPDP)',
            'oam'          => 'OAM',
            'ivass'        => 'IVASS',
            'banca_italia' => 'Banca d\'Italia',
            'client'       => 'Cliente (titolare del trattamento)',
            'internal'     => 'Audit interno',
            'other'        => 'Altro',
        ];
    }

    public static function getAuditableTypeOptions(): array
    {
        return [
            Client::class  => 'Cliente (responsabile del trattamento)',
            Company::class => 'Azienda',
        ];
    }

    // ── booted ───────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $record) {
            if (empty($record->company_id) && auth()->check()) {
                if (function_exists('filament') && filament()->getTenant()) {
                    $record->company_id = filament()->getTenant()->id;
                } elseif (auth()->user()->companies()->exists()) {
                    $record->company_id = auth()->user()->companies()->first()->id;
                }
            }
        });

        // Quando tutti i rilievi sono chiusi → aggiorna stato audit
        static::saved(function (self $record) {
            if ($record->findings()->exists() && !$record->openFindings()->exists()) {
                $record->updateQuietly(['status' => 'completed']);
            }
        });
    }
}
