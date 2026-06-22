<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuspiciousActivityReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';
    protected $table = 'suspicious_activity_reports';

    protected $fillable = [
        'client_id',
        'reporter_type',
        'reporter_id',
        'anomalies_codes',
        'description',
        'status',
        'reported_at',
    ];

    protected $casts = [
        'anomalies_codes' => 'array',
        'description' => 'encrypted',
        'reported_at' => 'datetime',
    ];

    public function reporter(): MorphTo
    {
        return $this->morphTo();
    }
}
