<?php

namespace App\Models;

use App\Enums\AuditStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audit extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    /**
     * Gli attributi assegnabili in massa (Mass Assignable).
     * In Laravel 13 si preferisce la notazione dei tipi in formato list.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'auditable_type',
        'auditable_id',
        'auditor_name',
        'organization_id',
        'requested_by_user_id',
        'scheduled_at',
        'executed_at',
        'status',
        'protocol_number',
        'origin_type',
        'execution_method',
        'scope',
        'outcome',
        'summary',
        'auditor_notes',
        'remediation_plan',
        'followup_date',
    ];

    /**
     * Definizione dei Casts (Nuovo standard nativo da Laravel 11/12/13 tramite metodo).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'date',
            'executed_at' => 'date',
            'followup_date' => 'date',
            // Cast nativo verso il PHP Enum (Filament lo adora)
            'status' => AuditStatus::class,
        ];
    }

    /**
     * I "Booted" del Modello.
     * Intercetta le azioni del ciclo di vita di Eloquent.
     */
    protected static function booted(): void
    {
        static::creating(function (Audit $audit) {
            // Se non è già stato specificato un company_id, assegna la prima Company presente
            if (blank($audit->company_id)) {
                $audit->company_id = \App\Models\Company::first()?->id;
            }
        });
    }

    /**
     * -----------------------------------------------------------------
     * RELAZIONI ELOQUENT
     * -----------------------------------------------------------------
     */

    /**
     * Relazione col Tenant (La Company principale del mediatore creditizio)
     * Fondamentale se usi il Multi-tenancy nativo di Filament 5.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Il Soggetto Controllato (Polimorfismo).
     * Può restituire un modello Impiegato (Employee), Produttore/Agente (Agent), ecc.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * L'Organismo di Vigilanza esterno che ha richiesto o imposto l'audit (es. OAM, IVASS).
     */
    public function organization(): BelongsTo
    {
        // Se hai chiamato il modello "Organization", usa Organization::class
        // Se lo hai chiamato "SupervisoryBody", usa SupervisoryBody::class
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * L'utente interno che ha richiesto l'apertura dell'audit (es. Responsabile Compliance).
     */
    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
