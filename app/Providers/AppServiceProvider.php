<?php

namespace App\Providers;

use App\Models\Audit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ComplaintRegistry;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Fornitore;
use App\Models\Pratica;
use App\Models\PROFORMA\Clienti;
use App\Models\Website;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Google\GoogleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;
use Unico\Core\UnicoCoreServiceProvider;

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
        config(['unico-core.documents.oam_semester_filter' => true]);

        // Anagrafiche sul database condiviso (connessione `core`, unico_core_test): le migration del pacchetto
        // non si eseguono da qui. Nei test UNICO_CORE_DB_CONNECTION coincide col default e le tabelle si creano con `migrate`.
        if (config('unico-core.connection') === config('database.default')) {
            $this->loadMigrationsFrom(UnicoCoreServiceProvider::migrationsPath());
        }

        Relation::morphMap([
            'audit' => Audit::class,
            'branch' => Branch::class,
            'cliente' => Clienti::class,
            'company' => Company::class,
            'complaint' => ComplaintRegistry::class,
            'document' => Document::class,
            'employee' => Employee::class,
            'fornitore' => Fornitore::class,
            'pratica' => Pratica::class,
            'website' => Website::class,
        ]);

        Event::listen(
            SocialiteWasCalled::class,
            [MicrosoftExtendSocialite::class, 'handle']
        );
        Event::listen(
            SocialiteWasCalled::class,
            [GoogleExtendSocialite::class, 'handle']
        );
    }
}
