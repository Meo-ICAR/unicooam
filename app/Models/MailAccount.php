<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUserCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Unico\Core\Models\MailAccount as CoreMailAccount;

class MailAccount extends CoreMailAccount
{
    use HasFactory, SoftDeletes;
    use ScopedToUserCompany;

    protected $orderBy = 'name';

    protected $orderDirection = 'asc';

    /**
     * Relazione Polimorfica che ora supporta sia ID interi che UUID stringhe.
     */
    public function mailable(): MorphTo
    {
        return $this->morphTo();
    }
}
