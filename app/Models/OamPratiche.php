<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUserCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Unico\Core\Models\OamPratica as CoreOamPratica;

class OamPratiche extends CoreOamPratica
{
    use HasFactory, SoftDeletes;
    use ScopedToUserCompany;

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
    /**
     * Pratiche che appartengono al semestre della propria riga (period): restano fuori quelle erogate
     * in un semestre precedente (salvo con storni nel semestre) e quelle inserite dopo la fine del semestre.
     */
    public function scopeDelSemestre(Builder $query): Builder
    {
        $start = "CASE WHEN RIGHT(period, 2) = '06' THEN CONCAT(LEFT(period, 4), '-01-01') ELSE CONCAT(LEFT(period, 4), '-07-01') END";
        $end = "CASE WHEN RIGHT(period, 2) = '06' THEN CONCAT(LEFT(period, 4), '-06-30') ELSE CONCAT(LEFT(period, 4), '-12-31') END";

        return $query
            ->where(fn (Builder $q) => $q
                ->whereNull('erogated_at')
                ->orWhereRaw("erogated_at >= {$start}")
                ->orWhere('importo_retrocesse', '>', 0))
            ->where(fn (Builder $q) => $q
                ->whereNull('sended_at')
                ->orWhereRaw("sended_at < DATE_ADD({$end}, INTERVAL 1 DAY)"));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
