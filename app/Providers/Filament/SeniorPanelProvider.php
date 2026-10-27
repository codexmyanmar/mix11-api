<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\MenuItem; 
use Filament\View\PanelsRenderHook; 
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route; 
use Illuminate\Support\Facades\Session; 
use Filament\Navigation\NavigationItem; 
// Pages
use App\Filament\Shared\Pages\GameBettingStatus;
use App\Filament\Super\Pages\EditProfile;
use App\Filament\Shared\Pages\SharedDashboard;

class SeniorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('senior')
            ->domain('senior.zvz-commerce.com')
            ->path('/')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->profile(EditProfile::class)
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('filament.hooks.user-panel-info')
            )
            ->routes(function () {
                // Load standard Filament routes
                require base_path('routes/filament/web/senior.php');

                // Custom GET route for Sidebar Logout
                Route::get('/logout-action', function () {
                    filament()->auth()->logout();
                    session()->invalidate();
                    session()->regenerateToken();
                    return redirect('/login'); // Redirect to login page
                });
            })
            ->navigationItems([
                NavigationItem::make('Profile')
                    ->label(fn() => __('Profile'))
                    ->url(fn (): string => EditProfile::getUrl())
                    ->icon('heroicon-o-user-circle')
                    ->group(fn() => __('Settings')) // Group it under Settings
                    ->sort(90),

                NavigationItem::make('Logout')
                    ->label(fn() => __('Sign out'))
                    ->url('/logout-action') // Use the custom route defined below
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->group(fn() => __('Settings')) // Group it under Settings
                    ->sort(99),
            ])
            ->discoverResources(in: app_path('Filament/Senior/Resources'), for: 'App\\Filament\\Senior\\Resources')
            ->resources([
                \App\Filament\Shared\Resources\AnnouncementResource::class,
                \App\Filament\Shared\Resources\BannerResource::class, // 
                \App\Filament\Shared\Resources\PromotionADSResource::class,
                \App\Filament\Shared\Resources\ContactChannelResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Senior/Pages'), for: 'App\\Filament\\Senior\\Pages')
            ->pages([
              //  Pages\Dashboard::class,
                GameBettingStatus::class,
                SharedDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Senior/Widgets'), for: 'App\\Filament\\Senior\\Widgets')
            ->widgets([
                            \App\Filament\Shared\Widgets\UserStatisticsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\SetLocale::class
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
