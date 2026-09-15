<?php

namespace App\Providers\Filament;

use AlizHarb\ActivityLog\ActivityLogPlugin;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->navigationGroups([
                //  NavigationGroup::make()->label('Pratiche'),
                //  NavigationGroup::make()->label('Contabilita'),
                NavigationGroup::make()->label('Anagrafiche'),  // ->collapsed(),
                NavigationGroup::make()->label('Documentazione')->collapsed(),
                NavigationGroup::make()->label('System')->collapsed(),
            ])
            ->brandLogo(asset('images/unicoOAM_banner.png'))
            // Opzionale: imposta un'altezza fissa se ti sembra troppo grande o piccolo
            //   ->brandLogoHeight('3rem')
            // Imposta l'icona del browser (favicon)
            ->favicon(asset('images/unicoOAM.png'))

            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->navigationItems([

                NavigationItem::make('Manuale Utente')
                    ->url(fn (): string => route('manuale-oam'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-document-arrow-down')
                    ->group('Documentazione') // Opzionale: raggruppa l'elemento in una sezione
                    ->sort(10), // Opzionale: posizionalo in fondo al menu
                NavigationItem::make('Manuale tecnico OAM')
                    ->url(fn (): string => route('manuale-operativo-oam'), shouldOpenInNewTab: true)
                    ->group('Documentazione') // Opzionale: raggruppa l'elemento in una sezione
                    ->icon('heroicon-o-book-open')
                    ->sort(10),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugin(
                FilamentSocialitePlugin::make()
                    // (required) Add providers corresponding with providers in `config/services.php`.
                    ->providers([
                        Provider::make('microsoft')
                            ->label('Microsoft')
                            ->icon('fab-microsoft')
                            ->color(Color::hex('#0078D4'))
                            ->outlined(false)
                            ->stateless(false),
                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google')
                            ->color(Color::hex('#4285F4'))
                            ->outlined(false)
                            ->stateless(false),
                    ])
                    ->registration(true)
            )
            ->plugin(
                ActivityLogPlugin::make()
                    ->label('Log')
                    ->pluralLabel('Logs')
                    ->navigationGroup('System')
            )
            ->authMiddleware([
                Authenticate::class,
            ])
            // Filament salva lo stato aperto/chiuso dei gruppi di navigazione in
            // localStorage e lo inizializza dai default PHP (->collapsed()) solo
            // se quella chiave non esiste ancora nel browser. Chi ha già visitato
            // il pannello prima che "Documentazione"/"System" avessero
            // ->collapsed() ha quindi uno stato salvato che ignora il nuovo
            // default. Questo hook azzera una tantum (per browser) quello stato,
            // cosi' viene ricalcolato dai default correnti; da quel momento in
            // poi torna a rispettare le scelte manuali dell'utente.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <script>
                        if (!localStorage.getItem('collapsedGroupsResyncedV1')) {
                            localStorage.removeItem('collapsedGroups');
                            localStorage.setItem('collapsedGroupsResyncedV1', '1');
                        }
                    </script>
                    HTML),
            );
    }
}
