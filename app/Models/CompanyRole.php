<?php

namespace App\Models;

use App\ValueObjects\OamSemester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyRole extends Model
{
    use HasFactory;

    // Definisce esplicitamente la tabella
    protected $connection = 'mysql';

    protected $table = 'company_roles';

    // Campi compilabili tramite assegnazione di massa
    protected $fillable = [
        'company_id',
        'name',
        'funzione',
        'is_external',
        'dal',
        'al',
        'execution_method',
        'expertName',
        'n',
    ];

    // Casting automatico dei tipi di dato
    protected function casts(): array
    {
        return [
            'is_external' => 'boolean',
            'dal' => 'date',
            'al' => 'date',
        ];
    }

    /**
     * Relazione: Un ruolo/ispezione appartiene a un'azienda.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopePerSemestreOam(Builder $query, OamSemester $semester): Builder
    {
        return $query->where('dal', '<=', $semester->end)
            ->where('al', '>=', $semester->start);

    }
}
