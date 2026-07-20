<?php

namespace App\Models\PROFORMA;

use App\Models\Document;
use App\Models\OamCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Pratica extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $connection = 'mysql_proforma';

    protected $table = 'proforma.pratiches';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'codice_pratica',
        'nome_cliente',
        'cognome_cliente',
        'codice_fiscale',
        'denominazione_agente',
        'partita_iva_agente',
        'denominazione_banca',
        'tipo_prodotto',
        'denominazione_prodotto',
        'data_inserimento_pratica',
        'stato_pratica',
        'rata',
        'erogato',
        'nrate',
        'sended_at',
        'approved_at',
        'erogated_at',
        'rejected_at',
        'amount',
        'net',
        'is_notowned',
        'upload_at', 'abi', 'abi_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data_inserimento_pratica' => 'date',
        'rata' => 'decimal:2',
        'erogato' => 'decimal:2',
        'nrate' => 'integer',
        'sended_at' => 'date',
        'rejected_at' => 'date',
        'approved_at' => 'date',
        'erogated_at' => 'date',
        'amount' => 'decimal:2',
        'net' => 'decimal:2',
        'is_notowned' => 'boolean',
        'upload_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the agent (fornitore) associated with the pratica.
     */
    public function agente()
    {
        return $this->belongsTo(Fornitore::class, 'partita_iva_agente', 'piva');
    }

    public function oamCode()
    {
        return $this->belongsTo(OamCode::class, 'tipo_prodotto', 'tipo_prodotto');
    }

    /**
     * Get the status of the pratica.
     */
    public function stato()
    {
        return $this->belongsTo(PraticheStato::class, 'stato_pratica', 'stato_pratica');
    }

    public function annullato()
    {
        return $this->stato()->is_rejected;
    }

    /**
     * Get the agent (fornitore) associated with the pratica.
     */
    public function provvigioni()
    {
        return $this->HasMany(Provvigione::class, 'id_pratica', 'id');
    }

    public function scopePerSemestreOam(Builder $query, $semester): Builder
    {
        return $query
            ->whereNull('rejected_at')
            ->where('data_inserimento_pratica', '>=', '2025-01-01') // Cutoff storico
            ->where('data_inserimento_pratica', '<', $semester->end)
            ->where('stato_pratica', '<>', 'INSERITA')
            ->where('is_notowned', 0)
            ->whereNotIn('tipo_prodotto', ['Utenza', 'Polizza'])
            ->where(function (Builder $q) use ($semester) {
                $q->whereNull('erogated_at')
                    ->orWhere(function (Builder $subQ) use ($semester) {
                        $subQ->where('erogated_at', '>=', $semester->start)
                            ->where('erogated_at', '<', $semester->end);
                    });
            });
    }

    /**
     * Relazione con la cronologia degli stati
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(PraticaStatusHistory::class, 'pratica_id')->orderBy('changed_at', 'desc');
    }

    /**
     * Boot del modello per intercettare gli eventi di aggiornamento
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function (Pratica $pratica) {
            // Se lo stato della pratica è cambiato rispetto al valore precedente nel DB
            if ($pratica->isDirty('stato_pratica')) {
                $oldStatus = $pratica->getOriginal('stato_pratica');
                $newStatus = $pratica->stato_pratica;
                $now = Carbon::now();

                // 1. Allinea automaticamente le date di milestone in base allo stato
                // Adatta i nomi degli stati in base a quelli reali restituiti dalle banche
                switch (strtolower($newStatus)) {
                    case 'inviata':
                    case 'istruttoria':
                        if (is_null($pratica->sended_at)) {
                            $pratica->sended_at = $now->toDateString();
                        }
                        break;
                    case 'approvata':
                    case 'deliberata':
                        if (is_null($pratica->approved_at)) {
                            $pratica->approved_at = $now->toDateString();
                        }
                        break;
                    case 'erogata':
                    case 'liquidata':
                        if (is_null($pratica->erogated_at)) {
                            $pratica->erogated_at = $now->toDateString();
                        }
                        break;
                    case 'respinta':
                    case 'annullata':
                        if (is_null($pratica->rejected_at)) {
                            $pratica->rejected_at = $now->toDateString();
                        }
                        break;
                }

                // 2. Registra automaticamente il cambio di stato nella tabella storica
                // Usiamo "saved" o lo inseriamo direttamente qui
                $pratica->statusHistory()->create([
                    'status_from' => $oldStatus,
                    'status_to' => $newStatus,
                    'changed_at' => $now,
                    'source' => request()->is('api/*') ? 'api_banca' : 'manual', // Rileva se aggiornato da un'integrazione/API
                    'notes' => $pratica->status_notes ?? 'Aggiornamento automatico di stato.',
                ]);
            }
        });
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Relazione diretta con le istanze dei requisiti generati per QUESTA specifica pratica.
     */
    public function requisitiOperativi(): HasMany
    {
        return $this->hasMany(PraticaRequisitoOperativo::class, 'pratica_id');
    }

    /**
     * Scorciatoia per accedere ai Requisiti di Catalogo legati a questa pratica,
     * includendo le informazioni dello stato operativo come pivot.
     */
    public function requisiti(): BelongsToMany
    {
        return $this->belongsToMany(
            PraticaRequisito::class,
            'pratica_requisiti_operativi',
            'pratica_id',
            'pratica_requisito_id'
        )
            ->withPivot(['id', 'stato', 'data_richiesta', 'data_completamento', 'note'])
            ->withTimestamps();
    }

    /**
     * Popola automaticamente i requisiti operativi in base al sottotipo di prodotto scelto.
     */
    public function generaRequisitiDaProdotto(): void
    {
        if (! $this->tipoprodotto_sub_id) {
            return;
        }

        // 1. Recupera le regole definite per questo sottotipo di prodotto
        $regole = RequisitoTipoFinanziamento::where('tipoprodotto_sub_id', $this->tipoprodotto_sub_id)
            ->orderBy('ordine')
            ->get();

        // 2. Crea i record operativi per la pratica
        foreach ($regole as $regola) {
            $this->requisitiOperativi()->firstOrCreate(
                ['pratica_requisito_id' => $regola->pratica_requisito_id],
                [
                    'stato' => 'da_richiedere',
                    'data_richiesta' => null,
                ]
            );
        }
    }

    /**
     * 2. Verifica se tutti i requisiti OBBLIGATORI della pratica sono stati completati
     */
    public function haRequisitiObbligatoriIncompleti(): bool
    {
        return $this->requisitiOperativi()
            ->where('is_obbligatorio', true)
            ->where('stato', '!=', 'approvato') // o 'completato'
            ->exists();
    }

    /**
     * 3. Ritorna la lista dei requisiti obbligatori ancora mancanti
     */
    public function getRequisitiObbligatoriMancanti(): Collection
    {
        return $this->requisitiOperativi()
            ->with('requisito')
            ->where('is_obbligatorio', true)
            ->where('stato', '!=', 'approvato')
            ->get();
    }

    /**
     * 4. Calcola la percentuale di completamento dei requisiti (utile per Progress Bar in Filament)
     */
    public function getPercentualeCompletamentoRequisitiAttribute(): int
    {
        $totale = $this->requisitiOperativi()->count();

        if ($totale === 0) {
            return 100;
        }

        $completati = $this->requisitiOperativi()
            ->where('stato', 'approvato')
            ->count();

        return (int) round(($completati / $totale) * 100);
    }

    /**
     * 5. Controlla se la pratica può passare a un nuovo stato (Blocco sicurezza)
     */
    public function puoAvanzareAStato(StatoPratica $nuovoStato): bool
    {
        // Se lo stato di destinazione richiede tutti i documenti pronti (es. FASCICOLO COMPLETO / DELIBERATA)
        if (in_array($nuovoStato->codice, ['fascicolo_completo', 'deliberata', 'approvata'])) {
            return ! $this->haRequisitiObbligatoriIncompleti();
        }

        return true;
    }
}
