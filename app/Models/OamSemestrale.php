<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Unico\Core\Models\OamSemestrale as CoreOamSemestrale;

class OamSemestrale extends CoreOamSemestrale
{
    use HasFactory;

    public $timestamps = false;

    // Cast precisi per la corretta formattazione dei dati numerici e decimali
    protected $casts = [
        // Numerici interi
        'intermediari_convenzionati' => 'integer',
        'intermediari_non_convenzionati' => 'integer',
        'pratiche_intermediate' => 'integer',
        'pratiche_lavorazione' => 'integer',
        'num_rivalse' => 'integer',
        // Decimali (Importi economici)
        'erogato_lordo' => 'decimal:2',
        'erogato_lavorazione' => 'decimal:2',
        'provv_clientela' => 'decimal:2',
        'provv_istituto_comp' => 'decimal:2',
        'premi_istituto_comp' => 'decimal:2',
        'payin_ass_banche' => 'decimal:2',
        'payin_ass_broker' => 'decimal:2',
        'payin_ass_broker_cap' => 'decimal:2',
        'payout_rete_credito' => 'decimal:2',
        'payout_rete_ass_banche' => 'decimal:2',
        'payout_rete_ass_broker' => 'decimal:2',
        'payout_rete_ass_broker_cap' => 'decimal:2',
        'importo_retrocesse' => 'decimal:2',
    ];

    /**
     * Relazione: Azienda / Tenant di riferimento
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
