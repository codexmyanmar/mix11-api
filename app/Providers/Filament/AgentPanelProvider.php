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
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Route; 
use Illuminate\Support\Facades\Session; 
use App\Filament\Shared\Pages\GameBettingStatus;
// Pages
use App\Filament\Master\Pages\EditProfile;
use App\Filament\Shared\Pages\SharedDashboard;

class AgentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('agent')
            ->domain('agent.zvz-commerce.com')
            ->path('/')
            ->login()
            ->colors([
                'primary' => Color::Amber,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'danger' => Color::Rose,
                'info' => Color::Sky,
            ])
            ->profile(EditProfile::class)
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('filament.hooks.user-panel-info')
            )
            ->routes(function () {
                // Load standard Filament routes
                require base_path('routes/filament/web/agent.php');

                // Custom GET route for Sidebar Logout
                Route::get('/logout-action', function () {
                    filament()->auth()->logout();
                    session()->invalidate();
                    session()->regenerateToken();
                    return redirect('/login');
                });
            })
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
            ->discoverResources(in: app_path('Filament/Agent/Resources'), for: 'App\\Filament\\Agent\\Resources')
            ->resources([
                // \App\Filament\Shared\Resources\AnnouncementResource::class,
                // \App\Filament\Shared\Resources\BannerResource::class,
                \App\Filament\Shared\Resources\PromotionADSResource::class,
                \App\Filament\Shared\Resources\ContactChannelResource::class,
                \App\Filament\Shared\Resources\PaymentMethodResource::class,
                \App\Filament\Shared\Resources\TopUpResource::class,
                \App\Filament\Shared\Resources\WithdrawResource::class,
                \App\Filament\Shared\Resources\UnitHistoryLogResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Agent/Pages'), for: 'App\\Filament\\Agent\\Pages')
            ->pages([
                SharedDashboard::class,
                GameBettingStatus::class
                
            ])
            ->discoverWidgets(in: app_path('Filament/Agent/Widgets'), for: 'App\\Filament\\Agent\\Widgets')
            ->widgets([
                \App\Filament\Agent\Widgets\ReferralLinkWidget::class,
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
            ])
            ->widgets([
              
            ]);
    }
}