<?php

namespace App\Models\Concerns;

use App\Models\Scopes\UserCompanyScope;

/**
 * Limita le query del modello alla company dell'utente autenticato e, per
 * i nuovi record senza company_id assegna quella dell'utente o, se vuota, la sua last_company_id.
 */
trait ScopedToUserCompany
{
    protected static function bootScopedToUserCompany(): void
    {
        static::addGlobalScope(new UserCompanyScope(static::includesSharedCompanyRows()));

        static::creating(function ($model): void {
            if (! blank($model->company_id) || ! auth()->hasUser()) {
                return;
            }

            $user = auth()->user();

            $model->company_id = $user->company_id ?? $user->last_company_id;
        });
    }

    /** Vero se restano visibili le righe senza company_id (dati condivisi). */
    protected static function includesSharedCompanyRows(): bool
    {
        return true;
    }
}
