<?php

namespace App\Models;

use App\Models\PROFORMA\Fornitore;
use App\ValueObjects\OamSemester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Unico\Core\Models\Branch as CoreBranch;

class Branch extends CoreBranch
{
    use HasFactory, SoftDeletes;

    protected $orderBy = 'name';

    protected $orderDirection = 'asc';

    /**
     * I cast dei campi.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_main_office' => 'boolean',
        'is_active' => 'boolean',
        'founded_at' => 'date',
        'dismissed_at' => 'date',
    ];

    /**
     * Relazione con la Company principale (Tenant)
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relazione Polimorfica.
     * Consente di ottenere il modello proprietario della filiale (es. Hotel, Call Center, ecc.)
     */
    public function branchable(): MorphTo
    {
        return $this->morphTo();
    }

    public function employee()
    {
        return $this->HasMany(Employee::class);
    }

    public function fornitore()
    {
        return $this->HasMany(Fornitore::class);
    }

    public function scopePerSemestreOam(Builder $query, OamSemester $semester): Builder
    {
        return $query->where('founded_at', '<=', $semester->end)
            ->where(function ($q) use ($semester) {
                $q->whereNull('dismissed_at')
                    ->orWhere('dismissed_at', '>=', $semester->start);
            });
    }
}
