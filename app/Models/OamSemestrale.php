<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class OamSemestrale extends Model
{
    use HasFactory;

    // Forziamo il nome esatto della tabella in italiano
    protected $connection = 'mysql';
    protected $table = 'oam_semestrales';

    public $timestamps = false;

    // Campi di sistema protetti che Filament non deve mostrare/modificare
    protected $guarded = [
        'id',
        //  'created_at',
        //  'updated_at',
    ];

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
