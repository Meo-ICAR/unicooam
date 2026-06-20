<?php

namespace App\Models\PROFORMA;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;;

/**
 * @property string $id
 * @property string|null $cf
 * @property string|null $coge
 * @property string|null $codice
 * @property string|null $name
 * @property string|null $nome
 * @property string|null $piva
 * @property string|null $email
 * @property string|null $regione
 * @property string|null $citta
 * @property string $company_id
 * @property int|null $customertype_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Clienti extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $connection = 'mysql_proforma';

    protected $table = 'clientis';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $orderBy = 'name';
    protected $orderDirection = 'asc';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
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
        'abi',
        'abi_name',
        'stipulated_at',
        'dismissed_at',
        'type',
        'oam',
        'oam_name',
        'oam_at',
        'numero_iscrizione_rui',
        'ivass',
        'ivass_at',
        'ivass_name',
        'ivass_section',
        'mandate_number',
        'start_date',
        'end_date',
        'is_exclusive',
        'status',
        'notes',
        'principal_type',
        'submission_type',
        'cf',
        'website',
        'is_reported',
        'privacy_contact_email',
        'dpo_email',
        'coge',
        'codice',
        'name',
        'nome',
        'piva',
        'email',
        'regione',
        'citta',
        'company_id',
        'customertype_id',
        'is_active',
        'is_dummy',
    ];

    /**
     * Cast dei tipi di dato nativi (es. Date, Booleani).
     * Questo permette a Laravel e Filament di trattare correttamente i campi.
     */
    protected $casts = [
        'stipulated_at' => 'date',
        'dismissed_at' => 'date',
        'oam_at' => 'date',
        'ivass_at' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_exclusive' => 'boolean',
        'is_reported' => 'boolean',
        'is_active' => 'boolean',
        'is_dummy' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope('order_by_name', function ($builder) {
            $builder->orderBy('name');
        });
    }

    /**
     * Cerca un cliente per name, prende la piva, cerca il fornitore con la stessa piva
     * e flag is_dummy = false, e ritorna il campo nome del fornitore.
     *
     * @param string $name
     * @return string|null
     */
    public static function getClienteNomeByName(string $name): ?string
    {
        $cliente = static::where('name', $name)->first();
        $nome = $cliente->nome;
        if ($cliente && $cliente->piva && $cliente->is_dummy) {
            $clientex = static::where('piva', $cliente->piva)
                ->where('is_dummy', false)
                ->first();
            $nome = $clientex->nome;
        }

        return $nome;
    }

    public static function getClienteTipo(string $name): ?string
    {
        $cliente = static::where('nome', $name)->first();
        $tipo = $cliente->principal_type;
        return $nome;
    }
}
