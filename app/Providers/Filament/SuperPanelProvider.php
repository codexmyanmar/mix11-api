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
use App\Filament\Super\Pages\EditProfile;
use App\Filament\Shared\Pages\GameBettingStatus;
use App\Filament\Shared\Pages\SharedDashboard;


class SuperPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('super')
            ->domain('super.zvz-commerce.com')
            ->path('/')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->profile(EditProfile::class)
            // 1. TOP RIGHT USER MENU (Language + Profile)
            ->userMenuItems([ 
    
            ])
            // 2. LEFT SIDEBAR ITEMS (Profile & Logout)
            ->navigationItems([
                NavigationItem::make('Profile')
                    ->label(fn() => __('Profile'))
                    ->url(fn (): string => EditProfile::getUrl())
                    ->icon('heroicon-o-user-circle')
                    ->group(fn() => __('Settings')) 
                    ->sort(90),

                NavigationItem::make('Logout')
                    ->label(fn() => __('Sign out'))
                    ->url('/logout-action') 
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->group(fn() => __('Settings')) 
                    ->sort(99),
            ])
            // 3. RENDER HOOK (Balance & Language Bar)
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('filament.hooks.user-panel-info')
            )
            // 4. ROUTES
            ->routes(function () {
                // Load standard Filament routes
                require base_path('routes/filament/web/super.php');

                // Custom GET route for Sidebar Logout
                Route::get('/logout-action', function () {
                    filament()->auth()->logout();
                    session()->invalidate();
                    session()->regenerateToken();
                    return redirect('/login'); 
                });
            })
            ->discoverResources(in: app_path('Filament/Super/Resources'), for: 'App\\Filament\\Super\\Resources')
            ->resources([
                \App\Filament\Shared\Resources\AnnouncementResource::class,
                \App\Filament\Shared\Resources\BannerResource::class, // 
                \App\Filament\Shared\Resources\PromotionADSResource::class,
                \App\Filament\Shared\Resources\ContactChannelResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Super/Pages'), for: 'App\\Filament\\Super\\Pages')
            ->pages([
             //   Pages\Dashboard::class,
                GameBettingStatus::class,
                SharedDashboard::class
            ])
            ->discoverWidgets(in: app_path('Filament/Super/Widgets'), for: 'App\\Filament\\Super\\Widgets')
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