<?php

namespace App\Models;

use App\Models\PROFORMA\Clienti;
use App\ValueObjects\OamSemester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Unico\Core\Models\Fornitore as CoreFornitore;

/**
 * Agenti di commercio / Collaboratori (Fornitori di servizi).
 * Legge e scrive sulla connessione core (unico_core_test in locale).
 */
class Fornitore extends CoreFornitore
{
    use HasFactory;

    protected $orderBy = 'name';

    protected $orderDirection = 'asc';

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $fornitore) {
            if (empty($fornitore->company_id) && auth()->check()) {
                $fornitore->company_id = auth()->user()->profile?->company_id;
            }
        });
    }

    public static function getFornitoreNomeByName(string $name): ?string
    {
        return static::where('name', $name)->first()?->nome;
    }

    public function websites(): MorphMany
    {
        return $this->morphMany(Website::class, 'websiteable');
    }

    public function complaints(): MorphMany
    {
        return $this->morphMany(ComplaintRegistry::class, 'complainant');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function branches(): MorphMany
    {
        return $this->morphMany(Branch::class, 'branchable');
    }

    public function scopePerSemestreOam(Builder $query, OamSemester $semester): Builder
    {
        return $query->where('stipulated_at', '<=', $semester->end)
            ->where(function ($q) use ($semester) {
                $q->whereNull('dismissed_at')
                    ->orWhere('dismissed_at', '>=', $semester->end);
            });
    }

    public function provvigioni(): HasMany
    {
        return $this->hasMany(ProvvigioniRule::class, 'clienti_id');
    }

    public function bancheBlacklist(): BelongsToMany
    {
        return $this->belongsToMany(Clienti::class, 'blacklist_clienti_fornitori', 'fornitore_id', 'cliente_id')
            ->withPivot(['motivo', 'data_inizio', 'data_fine'])
            ->withTimestamps();
    }

    public function isBlacklistedBy(string $clienteId): bool
    {
        return $this->bancheBlacklist()
            ->where('cliente_id', $clienteId)
            ->where(function ($query) {
                $query->whereNull('data_fine')
                    ->orWhere('data_fine', '>=', now());
            })
            ->exists();
    }
}
