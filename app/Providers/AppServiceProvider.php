<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'fornitore' => \App\Models\PROFORMA\Fornitore::class,
            'cliente' => \App\Models\PROFORMA\Clienti::class,
            'employee' => \App\Models\Employee::class,
            'company' => \App\Models\Company::class,
            'audit' => \App\Models\Audit::class,
            'complaint' => \App\Models\ComplaintRegistry::class,
            'document' => \App\Models\Document::class,
            'website' => \App\Models\Website::class,
            //     'fornitore' => \App\Models\PROFORMA\Fornitore::class,
            //    'cliente'   => \App\Models\PROFORMA\Clienti::class,
        ]);
    }
}
