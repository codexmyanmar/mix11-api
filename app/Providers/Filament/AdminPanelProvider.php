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
use Filament\Navigation\NavigationItem;
use Filament\View\PanelsRenderHook; 
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route; 
use App\Filament\Shared\Pages\GameBettingStatus;

// Pages
use App\Filament\Admin\Pages\EditProfile;
use App\Filament\Shared\Pages\SharedDashboard;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->domain('admin.zvz-commerce.com')
            ->path('/')
            ->login()
            ->profile(EditProfile::class) 
            ->colors([
                'primary' => Color::Amber,
            ])
            // 1. TOP RIGHT USER MENU
            ->userMenuItems([ 
                MenuItem::make()
                    ->label(fn() => __('မြန်မာ')) 
                    ->url('/lang/my')
                    ->icon('heroicon-m-language'),
                
                MenuItem::make()
                    ->label(fn() => __('အင်္ဂလိပ်')) 
                    ->url('/lang/en')
                    ->icon('heroicon-m-globe-alt'),
                
                MenuItem::make()
                    ->label(fn() => __('ပရိုဖိုင်'))
                    ->url(fn (): string => EditProfile::getUrl())
                    ->icon('heroicon-m-user-circle'),
            ])
            // 2. LEFT SIDEBAR ITEMS (Profile & Logout)
            ->navigationItems([
                NavigationItem::make('Profile')
                    ->label(fn() => __('ပရိုဖိုင်'))
                    ->url(fn (): string => EditProfile::getUrl())
                    ->icon('heroicon-o-user-circle')
                    ->group(fn() => __('ဆက်တင်များ'))
                    ->sort(90),

                NavigationItem::make('Logout')
                    ->label(fn() => __('ထွက်ရန်'))
                    ->url('/logout-action')
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->group(fn() => __('ဆက်တင်များ'))
                    ->sort(99),
            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('filament.language-switch')
            )   
            // 3. ROUTES
            ->routes(function () {
                require base_path('routes/filament/web/admin.php');

                Route::get('/logout-action', function () {
                    filament()->auth()->logout();
                    session()->invalidate();
                    session()->regenerateToken();
                    return redirect('/login');
                });
            })
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
              //  Pages\Dashboard::class,
                GameBettingStatus::class,
                SharedDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
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