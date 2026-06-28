<?php

namespace App\Providers\Filament;

use App\Models\User;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Filament\Support\Colors;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->navigationGroups([
                //  NavigationGroup::make()->label('Pratiche'),
                //  NavigationGroup::make()->label('Contabilita'),
                NavigationGroup::make()->label('Anagrafiche'),  // ->collapsed(),
                NavigationGroup::make()->label('Impostazioni')->collapsed(),
            ])
            ->brandLogo(asset('images/unicoOAM.png'))
            // Opzionale: imposta un'altezza fissa se ti sembra troppo grande o piccolo
            //   ->brandLogoHeight('3rem')
            // Imposta l'icona del browser (favicon)
            ->favicon(asset('images/unicoOAM.png'))
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
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
                        // Create a provider 'gitlab' corresponding to the Socialite driver with the same name.
                        Provider::make('microsoft')
                            ->label('Microsoft')
                            ->icon('fab-microsoft')
                            //   ->color(Color::hex('#2f2a6b'))
                            ->outlined(false)
                            ->stateless(false),
                        //   ->scopes(['...'])
                        //    ->with(['...']),
                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google')
                            //   ->color(Color::hex('#2f2a6b'))
                            ->outlined(false)
                            ->stateless(false),
                        //   ->scopes(['...'])
                        //    ->with(['...']),
                    ])
                // (optional) Override the panel slug to be used in the oauth routes. Defaults to the panel's configured path.
                //   ->slug('admin')
                // (optional) Enable/disable registration of new (socialite-) users.
                //   ->registration(true)
                // (optional) Enable/disable registration of new (socialite-) users using a callback.
                // In this example, a login flow can only continue if there exists a user (Authenticatable) already.
                //   ->registration(fn(string $provider, SocialiteUserContract $oauthUser, ?Authenticatable $user) => (bool) $user)
                // (optional) Change the associated model class.
                //    ->userModelClass(User::class)
                // (optional) Change the associated socialite class (see below).
                //   ->socialiteUserModelClass(SocialiteUser::class)
            )
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
