<?php

namespace App\Models;

use App\Models\Concerns\LogsComplianceActivity;
use App\Models\PROFORMA\Clienti;
use App\ValueObjects\OamSemester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Unico\Core\Models\SuspiciousActivityReport as CoreSuspiciousActivityReport;

class SuspiciousActivityReport extends CoreSuspiciousActivityReport
{
    use LogsComplianceActivity, SoftDeletes;

    /**
     * I cast nativi per gli attributi del database.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reported_at' => 'datetime',
        'anomalies_codes' => 'array',  // Converte automaticamente JSON in array PHP e viceversa
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI (RELAZIONI ELOQUENT)
    // ==========================================

    /**
     * Relazione polimorfica (UUID).
     * Identifica il soggetto che ha effettuato la segnalazione (es: Agent, Employee).
     * Perfetto per MorphToSelect o campi polimorfici in Filament.
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relazione verso l'azienda (Company).
     * Nota: usa UUID come chiave logica esterna.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Relazione verso il Cliente (se collegato direttamente alla segnalazione).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Clienti::class, 'client_id');
    }

    public function scopePerSemestreOam(Builder $query, OamSemester $semester): Builder
    {
        return $query->where('reported_at', '<=', $semester->end)
            ->where('reported_at', '>=', $semester->start);

    }
}
