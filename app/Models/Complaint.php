<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'complaints';

    protected $fillable = [
        'company_id',
        'client_id',
        'employee_id',
        'received_at',
        'subject',
        'description',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'received_at' => 'date',
        'resolved_at' => 'date',
    ];

    protected $appends = [
        'days_elapsed',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function daysElapsed(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if (!$this->received_at instanceof Carbon) {
                    return 0;
                }
                $end = $this->resolved_at instanceof Carbon ? $this->resolved_at : now();
                return (int) $this->received_at->diffInDays($end);
            },
        );
    }

    public function isOverdue(): bool
    {
        $closedStatuses = ['accolto', 'respinto'];
        return !in_array($this->status, $closedStatuses) && $this->days_elapsed > 45;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
