<?php

namespace App\Models;

use App\Models\Concerns\LogsComplianceActivity;
use Database\Factories\QualityReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualityReview extends Model
{
    /** @use HasFactory<QualityReviewFactory> */
    use HasFactory, LogsComplianceActivity, SoftDeletes;

    protected $connection = 'mysql';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'reviewer_user_id',
        'period_from',
        'period_to',
        'sample_size',
        'filters',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'sample_size' => 'integer',
            'filters' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    /**
     * Un Audit (origin_type = quality) per ogni pratica campionata.
     */
    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    /**
     * Pratiche del campione già valutate (con un esito).
     */
    public function reviewedCount(): int
    {
        return $this->audits()->whereNotNull('outcome')->count();
    }
}
