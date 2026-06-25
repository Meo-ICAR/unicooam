<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DocumentSchedule extends Model
{
    protected $fillable = [
        'document_id',
        'documentable_group_key',
        'document_name',
        'document_type_name',
        'entity_name',
        'documentable_type',
        'expires_at',
        'days_until_expiry',
        'status',
        'reminders_count',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
