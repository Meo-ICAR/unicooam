<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitoTipoFinanziamento extends Model
{
    protected $table = 'requisito_tipo_finanziamento';

    public $timestamps = false; // Tabella pivot personalizzata senza timestamps di default

    protected $fillable = [
        'tipoprodotto_id',
        'tipoprodotto_sub_id',
        'pratica_requisito_id',
        'obbligatorio',
        'ordine',
    ];

    protected $casts = [
        'obbligatorio' => 'boolean',
        'ordine' => 'integer',
    ];

    public function requisito(): BelongsTo
    {
        return $this->belongsTo(PraticaRequisito::class, 'pratica_requisito_id');
    }

    public function subTipoProdotto(): BelongsTo
    {
        return $this->belongsTo(TipoProdottoSub::class, 'tipoprodotto_sub_id');
    }
}
