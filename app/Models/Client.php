<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Unico\Core\Models\Client as CoreClient;

class Client extends CoreClient
{
    /**
     * Il cast degli attributi ai tipi nativi.
     * Garantisce che i tinyint diventino booleani puri e gestisce i timestamp.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Booleani
        'is_person' => 'boolean',
        'is_pep' => 'boolean',
        'is_sanctioned' => 'boolean',
        'is_remote_interaction' => 'boolean',
        'is_company' => 'boolean',
        'is_lead' => 'boolean',
        'privacy_consent' => 'boolean',
        'is_client' => 'boolean',
        'is_requiredApprovation' => 'boolean',
        'is_approved' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_art108' => 'boolean',
        'is_consultant_gdpr' => 'boolean',
        'is_iso27001_certified' => 'boolean',
        'is_dummy' => 'boolean',

        // decimali
        'salary' => 'decimal:2',
        'salary_quote' => 'decimal:2',

        // Date e Timestamp
        'general_consent_at' => 'datetime',
        'privacy_policy_read_at' => 'datetime',
        'consent_special_categories_at' => 'datetime',
        'consent_sic_at' => 'datetime',
        'consent_marketing_at' => 'datetime',
        'consent_profiling_at' => 'datetime',
        'acquired_at' => 'datetime',
        'blacklist_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // RELAZIONI
    // =========================================================================

    /**
     * Relazione con il Tenant / Azienda proprietaria.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relazione con la classificazione/tipo del cliente.
     */
    public function clientType(): BelongsTo
    {
        return $this->belongsTo(ClientType::class, 'client_type_id');
    }

    /**
     * Il client/lead di origine che ha generato/fornito questo contatto (Self-referencing).
     */
    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'leadsource_id');
    }

    /**
     * I lead generati da questo specifico cliente (Self-referencing).
     */
    public function generatedLeads(): HasMany
    {
        return $this->hasMany(Client::class, 'leadsource_id');
    }

    /**
     * Se i documenti polimorfici sono collegati anche ai clienti (documentable_type = 'client').
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function companyRelations()
    {
        return $this->hasMany(ClientRelation::class, 'company_id');
    }

    public function personRelations()
    {
        return $this->hasMany(ClientRelation::class, 'client_id');
    }

    public function clientMandates(): HasMany
    {
        return $this->hasMany(ClientMandate::class);
    }

    public function clientPratiches(): HasMany
    {
        return $this->hasMany(Pratica::class, 'codice_fiscale', 'tax_code');
    }

    public function activeMandates(): HasMany
    {
        return $this->clientMandates()->where('stato', 'attivo');
    }

    public function hasActiveMandates(): bool
    {
        return $this->activeMandates()->exists();
    }

    public function getLatestMandate()
    {
        return $this->clientMandates()->latest()->first();
    }

    public function getTotalMandateAmount(): float
    {
        return $this
            ->clientMandates()
            ->whereNotNull('importo_richiesto_mandato')
            ->sum('importo_richiesto_mandato');
    }
}
