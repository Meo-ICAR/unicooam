<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoprodottoSub extends Model
{
    // Specifichiamo il nome della tabella reale
    protected $connection = 'mysql_proforma';

    protected $table = 'proforma.tipoprodotto_sub';

    /**
     * I campi che possono essere assegnati massivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipoprodotto_id',
        'name',
        'code',
        'vincoli',
    ];

    /**
     * Ottiene il prodotto finanziario principale a cui appartiene questo sottoprodotto.
     * Relazione Molti a 1.
     */
    public function tipoProdotto(): BelongsTo
    {
        return $this->belongsTo(Tipoprodotto::class, 'tipoprodotto_id');
    }

    /**
     * Ottiene i sottoprodotti associati a questo prodotto finanziario.
     * Relazione 1 a Molti.
     */
    public function limits(): HasMany
    {
        // Specifichiamo la chiave esterna poiché il modello non si chiama 'TipoprodottoSub' standard
        return $this->hasMany(TipoprodottoSubConstraint::class, 'tipoprodotto_sub_id');
    }
}
