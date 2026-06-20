<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OamPratiche extends Model
{
    use HasFactory, SoftDeletes;

    // Definito esplicitamente per mappare esattamente il nome della tua tabella
    protected $table = 'oam_pratiches';

    // Proteggiamo i campi di sistema
    protected $guarded = [
        'id', 
        'created_at', 
        'updated_at', 
        'deleted_at'
    ];

    // Casting esplicito dei tipi di dato
    protected $casts = [
        // Interi
        'intermediari_convenzionati' => 'integer',
        'intermediari_non_convenzionati' => 'integer',
        'pratiche_intermediate' => 'integer',
        'pratiche_lavorazione' => 'integer',
        'num_rivalse' => 'integer',
        
        // Decimali (Importi)
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
   

        // Date (Datetime)
        'sended_at' => 'datetime',
        'approved_at' => 'datetime',
        'erogated_at' => 'datetime',
        'rejected_at' => 'datetime',
        'storned_at' => 'datetime',

               
    
    ];

    /**
     * Relazione: Azienda / Tenant
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}