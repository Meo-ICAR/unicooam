<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OamArchivioRiga extends Model
{
    public const TIPO_SEMESTRALE = 'semestrale';

    public const TIPO_PRATICA = 'pratica';

    protected $table = 'oam_archivio_righe';

    public $timestamps = false;

    protected $fillable = ['oam_archivio_id', 'tipo', 'dati'];

    protected $casts = ['dati' => 'array'];

    public function archivio(): BelongsTo
    {
        return $this->belongsTo(OamArchivio::class, 'oam_archivio_id');
    }
}
