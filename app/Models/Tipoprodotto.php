<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class TipoProdotto extends Model
{
    protected $connection = 'mysql_proforma';

    protected $table = 'proforma.tipoprodotto';

    protected $primaryKey = 'tipo_prodotto';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'tipo_prodotto',
        'oam',
    ];

    /**
     * Mappa virtualmente il campo 'tipo_prodotto' sulla proprietà 'name'.
     * In questo modo $prodotto->name restituirà il valore di tipo_prodotto.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tipo_prodotto,
            set: fn (string $value) => ['tipo_prodotto' => $value]
        );
    }
}
