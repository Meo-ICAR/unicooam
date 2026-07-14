<?php

namespace App\Models\PROFORMA;

use App\Models\OamCode;
use App\ValueObjects\OamSemester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
        'upload_at',
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

    public function scopePerSemestreOam(Builder $query, OamSemester $semester): Builder
    {
        return $query->where('erogated_at', '<=', $semester->end)
            ->where('erogated_at', '>=', $semester->start);

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
}
