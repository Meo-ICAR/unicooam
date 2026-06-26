<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        'documentable_id',
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

    public function documentable(): MorphTo
    {
        return $this->morphTo('documentable', 'documentable_type', 'documentable_id');
    }

    protected function entity(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->documentable_type || !$this->documentable_id) {
                    return null;
                }

                // Recuperiamo la classe del Model dal morphMap
                $modelClass = Relation::getMorphedModel($this->documentable_type);

                return $modelClass ? $modelClass::find($this->documentable_id) : null;
            }
        );
    }
}
