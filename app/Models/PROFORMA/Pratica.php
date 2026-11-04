<?php

namespace App\Models\PROFORMA;

use App\Models\Concerns\ScopedToUserCompany;

/**
 * Alias di compatibilità: le pratiche stanno su unico-core.
 *
 * @deprecated Usare App\Models\Pratica
 */
class Pratica extends \App\Models\Pratica
{
    use ScopedToUserCompany;
}
