<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Unico\Core\Models\MailAccount as CoreMailAccount;

class MailAccount extends CoreMailAccount
{
    use HasFactory, SoftDeletes;
    protected $orderBy = 'name';
    protected $orderDirection = 'asc';

    protected $casts = [
        'is_pec' => 'boolean',
        'is_active' => 'boolean',
        'incoming_port' => 'integer',
        'smtp_port' => 'integer',
        'incoming_password' => 'encrypted',  // Cifra la password nel DB in modo sicuro
        'smtp_password' => 'encrypted',  // Cifra la password nel DB in modo sicuro
    ];

    /**
     * Relazione Polimorfica che ora supporta sia ID interi che UUID stringhe.
     */
    public function mailable(): MorphTo
    {
        return $this->morphTo();
    }
}
