<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditFinding extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'audit_id',
        'company_id',
        'title',
        'description',
        'severity',
        'requires_investigation',
        'investigation_notes',
        'investigation_deadline',
        'requires_corrective_action',
        'corrective_action_description',
        'remediation_id',
        'corrective_action_deadline',
        'status',
        'resolved_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'requires_investigation'     => 'boolean',
            'requires_corrective_action' => 'boolean',
            'investigation_deadline'     => 'date',
            'corrective_action_deadline' => 'date',
            'resolved_at'                => 'date',
            'created_at'                 => 'datetime',
            'updated_at'                 => 'datetime',
            'deleted_at'                 => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function remediation(): BelongsTo
    {
        // remediations è su mariadb — recupera senza FK constraint
        return $this->belongsTo(Remediation::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['resolved', 'accepted_risk', 'closed']);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeOverdue($query)
    {
        return $query->open()
            ->where(fn($q) => $q
                ->where(fn($q2) => $q2
                    ->whereNotNull('corrective_action_deadline')
                    ->where('corrective_action_deadline', '<', now())
                )
                ->orWhere(fn($q2) => $q2
                    ->whereNotNull('investigation_deadline')
                    ->where('investigation_deadline', '<', now())
                )
            );
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getSeverityLabelAttribute(): string
    {
        return self::getSeverityOptions()[$this->severity] ?? $this->severity;
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'observation' => 'gray',
            'minor'       => 'info',
            'major'       => 'warning',
            'critical'    => 'danger',
            default       => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open'          => 'danger',
            'in_progress'   => 'warning',
            'resolved'      => 'success',
            'accepted_risk' => 'gray',
            'closed'        => 'success',
            default         => 'gray',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, ['resolved', 'accepted_risk', 'closed'])) {
            return false;
        }
        $deadline = $this->corrective_action_deadline ?? $this->investigation_deadline;
        return $deadline?->isPast() ?? false;
    }

    // ── Options ──────────────────────────────────────────────────────────────

    public static function getSeverityOptions(): array
    {
        return [
            'observation' => 'Osservazione',
            'minor'       => 'Rilievo minore',
            'major'       => 'Rilievo maggiore',
            'critical'    => 'Critico',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'open'          => 'Aperto',
            'in_progress'   => 'In lavorazione',
            'resolved'      => 'Risolto',
            'accepted_risk' => 'Rischio accettato',
            'closed'        => 'Chiuso',
        ];
    }
}
