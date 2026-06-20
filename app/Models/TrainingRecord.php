<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'trainable_type',
        'trainable_id',
        'regulatory_framework',
        'course_title',
        'course_description',
        'provider',
        'trainer',
        'delivery_mode',
        'training_date',
        'expiry_date',
        'hours',
        'outcome',
        'score',
        'certificate_issued',
        'certificate_number',
        'notes',
    ];

    protected $casts = [
        'training_date' => 'date',
        'expiry_date' => 'date',
        'hours' => 'decimal:1',
        'score' => 'decimal:2',
        'certificate_issued' => 'boolean',
    ];

    /**
     * Relazione polimorfica: recupera il modello associato al corso
     * (es. puo' essere un User, un Employee, un Consulente, ecc.)
     */
    public function trainable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relazione con l'Azienda
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
