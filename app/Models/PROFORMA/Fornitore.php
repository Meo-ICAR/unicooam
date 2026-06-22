<?php
// app/Models/Fornitore.php
namespace App\Models\PROFORMA;

use App\Models\PROFORMA\Provvigione;
use App\Models\ComplaintRegistry;
use App\Models\TrainingRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Fornitore extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql_proforma';
    protected $table = 'fornitoris';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $orderBy = 'name';
    protected $orderDirection = 'asc';

    protected $fillable = [
        'codice',
        'coge',
        'name',
        'pec',
        'email_private',
        'nome',
        'natoil',
        'indirizzo',
        'comune',
        'cap',
        'prov',
        'tel',
        'coordinatore',
        'piva',
        'cf',
        'nomecoge',
        'nomefattura',
        'email',
        'anticipo',
        'enasarco',
        'anticipo_residuo',
        'contributo',
        'contributo_description',
        'anticipo_description',
        'issubfornitore',
        'operatore',
        'iscollaboratore',
        'isdipendente',
        'regione',
        'citta',
        'company_id',
        'contributoperiodicita',
        'contributodalmese'
    ];

    protected $casts = [
        'natoil' => 'date',
        'anticipo' => 'decimal:2',
        'anticipo_residuo' => 'decimal:2',
        'contributo' => 'decimal:2',
        'issubfornitore' => 'boolean',
        'iscollaboratore' => 'boolean',
        'isdipendente' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'contributo_description' => 'Contributo spese',
        'anticipo_description' => 'Anticipo attuale',
        'issubfornitore' => false,
    ];

    /**
     * Cerca un cliente per name, prende la piva, cerca il fornitore con la stessa piva
     * e flag is_dummy = false, e ritorna il campo nome del fornitore.
     *
     * @param string $name
     * @return string|null
     */
    public static function getFornitoreNomeByName(string $name): ?string
    {
        $cliente = static::where('name', $name)->first();
        $nome = $cliente->nome;

        return $nome;
    }

    public function complaints(): MorphMany
    {
        return $this->morphMany(ComplaintRegistry::class, 'complainant');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
