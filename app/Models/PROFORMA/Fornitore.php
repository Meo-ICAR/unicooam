<?php

namespace App\Models\PROFORMA;

use App\Models\Concerns\ScopedToUserCompany;

/**
 * Alias di compatibilità: l'anagrafica fornitori sta su unico-core.
 *
 * @deprecated Usare App\Models\Fornitore
 */
class Fornitore extends \App\Models\Fornitore
{
    use ScopedToUserCompany;
}
