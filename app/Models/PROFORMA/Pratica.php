<?php

namespace App\Models\PROFORMA;

use App\Models\Document;
use App\Models\OamCode;
use App\Models\PraticaRequisito;
use App\Models\PraticaRequisitoOperativo;
use App\Models\PraticaStato;
use App\Models\RequisitoTipoFinanziamento;
use App\ValueObjects\OamSemester;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class Pratica extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $connection = 'mysql_proforma';

    protected $table = 'pratiches';

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
     * Etichetta leggibile (usata dalle colonne polimorfiche, es. negli Audit).
     */
    public function getNameAttribute(): string
    {
        return trim($this->codice_pratica.' - '.$this->cognome_cliente.' '.$this->nome_cliente);
    }

    /**
     * Get the agent (fornitore) associated with the pratica.
     */
    public function agente()
    {
        return $this->belongsTo(Fornitore::class, 'partita_iva_agente', 'piva');
    }

    /**
     * Get the agent (fornitore) associated with the pratica.
     */
    public function istituto()
    {
        return $this->belongsTo(Clienti::class, 'denominazione_banca', 'name');
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
        return $this->belongsTo(PraticaStati::class, 'stato_pratica', 'stato_pratica');
    }

    public function annullato(): bool
    {
        return (bool) ($this->stato?->is_rejected ?? false);
    }

    /**
     * Get the agent (fornitore) associated with the pratica.
     */
    public function provvigioni()
    {
        return $this->HasMany(Provvigione::class, 'id_pratica', 'id');
    }

    /**
     * Data di rifiuto vista dal semestre: l'anno futuro e' riportato all'anno del
     * semestre; un rifiuto non avvenuto prima della fine del semestre vale NULL.
     */
    public function rejectedAtNelSemestre(OamSemester $semester): ?CarbonInterface
    {
        if ($this->rejected_at === null) {
            return null;
        }

        $rejectedAt = $this->rejected_at->year > $semester->end->year
            ? $this->rejected_at->copy()->subYears($this->rejected_at->year - $semester->end->year)
            : $this->rejected_at;

        return $rejectedAt->toDateString() < $semester->end->toDateString() ? $rejectedAt : null;
    }

    public function scopePerSemestreOam(Builder $query, ?OamSemester $semester = null): Builder
    {
        $semester ??= OamSemester::current();

        return $query
            // Un rifiuto non puo' essere nel futuro (anno > anno del semestre => anno del
            // semestre) e uno successivo alla fine del semestre non conta per il semestre.
            ->where(fn (Builder $q) => $q->whereNull('rejected_at')->orWhereRaw(
                'IF(YEAR(rejected_at) > ?, DATE_SUB(rejected_at, INTERVAL (YEAR(rejected_at) - ?) YEAR), rejected_at) >= ?',
                [$semester->end->year, $semester->end->year, $semester->end->toDateString()]
            ))
            ->where('data_inserimento_pratica', '>=', '2025-01-01') // Cutoff storico
            ->where('stato_pratica', '<>', 'INSERITA')
            ->whereNotIn('stato_pratica', ['DECLINATA', 'RINUNCIA CLIENTE', 'PRATICA RESPINTA'])
            ->where('is_notowned', 0)
            // Presente prima della fine del semestre: discrimina sended_at; se manca vale
            // approved_at e, in assenza anche di questo, la data di inserimento.
            ->whereRaw('COALESCE(sended_at, approved_at, data_inserimento_pratica) <= ?', [$semester->end])
            ->whereNotIn('tipo_prodotto', ['Utenza', 'Polizza'])
            ->where(function (Builder $q) use ($semester) {
                $q->whereNull('erogated_at')
                    ->orWhere('erogated_at', '>=', $semester->start);
            });
    }

    /**
     * Relazione con la cronologia degli stati
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(PraticaStatusHistory::class, 'pratica_id')->orderBy('changed_at', 'desc');
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
        return $this->hasMany(PraticaRequisitoOperativo::class, 'requestable_id')
            ->withAttributes(['requestable_type' => 'pratica']);
    }

    /**
     * Scorciatoia per accedere ai Requisiti di Catalogo legati a questa pratica,
     * includendo le informazioni dello stato operativo come pivot.
     */
    public function requisiti(): BelongsToMany
    {
        return $this->belongsToMany(
            PraticaRequisito::class,
            'document_requests',
            'requestable_id',
            'document_type_id'
        )
            ->wherePivot('requestable_type', 'pratica')
            ->withPivot(['id', 'status', 'requested_at', 'completed_at', 'notes'])
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
            ->orderBy('sort_order')
            ->get();

        // 2. Crea i record operativi per la pratica
        foreach ($regole as $regola) {
            $this->requisitiOperativi()->firstOrCreate(
                ['document_type_id' => $regola->document_type_id],
                [
                    'status' => 'da_richiedere',
                    'requested_at' => null,
                    'is_required' => $regola->is_required,
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
            ->where('is_required', true)
            ->where('status', '!=', 'approvato') // o 'completato'
            ->exists();
    }

    /**
     * 3. Ritorna la lista dei requisiti obbligatori ancora mancanti
     */
    public function getRequisitiObbligatoriMancanti(): Collection
    {
        return $this->requisitiOperativi()
            ->with('requisito')
            ->where('is_required', true)
            ->where('status', '!=', 'approvato')
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
            ->where('status', 'approvato')
            ->count();

        return (int) round(($completati / $totale) * 100);
    }

    /**
     * 5. Controlla se la pratica può passare a un nuovo stato (Blocco sicurezza)
     */
    public function puoAvanzareAStato(PraticaStato $nuovoStato): bool
    {
        // Se lo stato di destinazione richiede tutti i documenti pronti (es. FASCICOLO COMPLETO / DELIBERATA)
        if (in_array($nuovoStato->codice, ['fascicolo_completo', 'deliberata', 'approvata'])) {
            return ! $this->haRequisitiObbligatoriIncompleti();
        }

        return true;
    }
}
