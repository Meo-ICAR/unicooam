<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Un utente con company_id non vede i dati di un'altra company.
 * Senza utente autenticato o con utente senza company_id non filtra.
 */
class UserCompanyScope implements Scope
{
    /**
     * @param  bool  $includeShared  Se vero restano visibili anche le righe senza company_id.
     */
    public function __construct(private readonly bool $includeShared = true) {}

    public function apply(Builder $builder, Model $model): void
    {
        // hasUser(): non risolve l'utente dalla sessione, cosi' la query di login sugli utenti non entra in loop.
        $companyId = auth()->hasUser() ? auth()->user()->company_id : null;

        if ($companyId === null) {
            return;
        }

        $column = $model->qualifyColumn('company_id');

        if (! $this->includeShared) {
            $builder->where($column, $companyId);

            return;
        }

        $builder->where(fn (Builder $query) => $query
            ->where($column, $companyId)
            ->orWhereNull($column));
    }
}
