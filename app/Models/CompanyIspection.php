<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyIspection extends Model
{
    // Specifichiamo la tabella visto che ha un nome personalizzato/particolare
    protected $table = 'company_ispections';

    // Disabilitiamo i timestamp (created_at/updated_at) perché non presenti nel tuo SQL
    public $timestamps = false;

    /**
     * I campi assegnabili in massa (Mass Assignable).
     */
    protected $fillable = [
        'company_id',
        'name',
        'dal',
        'al',
        'execution_method',
        'ispectorName',
        'n',
    ];

    /**
     * I cast nativi di Laravel 13 (usando il nuovo metodo casts() introdotto di recente).
     */
    protected function casts(): array
    {
        return [
            'dal' => 'date',
            'al' => 'date',
            'n' => 'integer',
        ];
    }

    /**
     * Relazione: Un'ispezione appartiene a una Company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
