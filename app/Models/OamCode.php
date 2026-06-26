<?php

namespace App\Models;

use App\Models\PROFORMA\Clienti;
use App\Models\ClientiOam;  // Assicurati di importarlo
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class OamCode extends Model
{
    use HasFactory;

    // Definito esplicitamente per mappare la tabella plurale corretta
    protected $connection = 'mysql';
    protected $table = 'oam_codes';

    protected $fillable = [
        'code',
        'name',
        'description',
        'tipo_prodotto',
        'is_dummy',
        'is_active'
    ];

    public function clienti(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Clienti::class,
                'clienti_oam',
                'oam_code_id',  // Invertito: prima la chiave di questo modello nella pivot
                'clienti_id'  // Poi la chiave del modello correlato
            )
            ->withPivot('dal', 'al')
            ->withTimestamps();
    }
}
