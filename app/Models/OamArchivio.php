<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OamArchivio extends Model
{
    use HasFactory;

    protected $table = 'oam_archivi';

    protected $fillable = [
        'company_id',
        'period',
        'note',
        'archived_by',
        'semestrali_count',
        'pratiche_count',
        'restored_at',
    ];

    protected $casts = [
        'semestrali_count' => 'integer',
        'pratiche_count' => 'integer',
        'restored_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function righe(): HasMany
    {
        return $this->hasMany(OamArchivioRiga::class);
    }

    /**
     * Etichetta per select e notifiche, es. "202601 — archiviato il 15/10/2026 (12 + 340 righe)".
     */
    public function label(): string
    {
        return "{$this->period} — archiviato il {$this->created_at?->format('d/m/Y H:i')} ({$this->semestrali_count} semestrali + {$this->pratiche_count} pratiche)";
    }
}
