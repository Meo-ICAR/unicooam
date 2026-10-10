<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Unico\Core\Models\ClientMandate as CoreClientMandate;

class ClientMandate extends CoreClientMandate
{
    use SoftDeletes;

    /**
     * Il cast degli attributi ai tipi nativi di PHP/Carbon.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Booleani da tinyint
        'oam_delivered' => 'boolean',

        // Decimali
        'importo_richiesto_mandato' => 'decimal:2',

        // Date (Cast a oggetti Date/Carbon senza ore)
        'data_firma_mandato' => 'date',
        'data_scadenza_mandato' => 'date',
        'data_consegna_trasparenza' => 'date',

        // Timestamp completi
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // =========================================================================
    // RELAZIONI
    // =========================================================================

    /**
     * Il cliente intestatario/coinvolto in questo mandato finanziario.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Relazione polimorfica con i documenti (se i mandati possiedono allegati fisici firmati).
     * Mappa documentable_type = 'App\Models\ClientMandate'
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
