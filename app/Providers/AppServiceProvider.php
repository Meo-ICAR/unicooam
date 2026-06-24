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
            'audit' => \App\Models\Audit::class,
            'branch' => \App\Models\Branch::class,
            'cliente' => \App\Models\PROFORMA\Clienti::class,
            'company' => \App\Models\Company::class,
            'complaint' => \App\Models\ComplaintRegistry::class,
            'document' => \App\Models\Document::class,
            'employee' => \App\Models\Employee::class,
            'fornitore' => \App\Models\PROFORMA\Fornitore::class,
            'website' => \App\Models\Website::class,
        ]);
    }
}
