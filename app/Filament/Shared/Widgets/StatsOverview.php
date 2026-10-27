<?php

namespace App\Filament\Shared\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\TopUp;
use App\Models\Withdraw;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();
        
        // Common stats for all roles
        $commonStats = [];
        
        // Role-specific stats
        return match($role) {
            'admin' => [
                Stat::make('Total Users', User::count())
                    ->description('All registered users')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('success'),
                Stat::make('Total Deposits', TopUp::where('status', 'approved')->sum('amount'))
                    ->description('Total amount deposited')
                    ->descriptionIcon('heroicon-m-currency-dollar')
                    ->color('warning'),
                Stat::make('Total Withdrawals', Withdraw::where('status', 'approved')->sum('amount'))
                    ->description('Total amount withdrawn')
                    ->descriptionIcon('heroicon-m-arrow-up')
                    ->color('danger'),
            ],
            'super' => [
                Stat::make('My Referrals', User::where('referrer_id', $user->id)->count())
                    ->description('Users you referred')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('success'),
                Stat::make('Commission Earned', $this->getCommissionEarned($user))
                    ->description('Total commission earned')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('warning'),
            ],
            'senior' => [
                Stat::make('Active Members', User::where('status', 1)->count())
                    ->description('Active members')
                    ->descriptionIcon('heroicon-m-user-plus')
                    ->color('success'),
            ],
            'master' => [
                Stat::make('My Downline', User::where('referrer_id', $user->id)->count())
                    ->description('Direct referrals')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('success'),
            ],
            'agent' => [
                Stat::make('My Customers', User::where('agent_id', $user->id)->count())
                    ->description('Customers under me')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('success'),
                Stat::make('Today\'s Sales', $this->getTodaySales($user))
                    ->description('Today\'s revenue')
                    ->descriptionIcon('heroicon-m-currency-bangladeshi')
                    ->color('warning'),
            ],
            default => [
                Stat::make('Welcome', 'Dashboard')
                    ->description('Welcome to your dashboard')
                    ->color('success'),
            ],
        };
    }
    
    private function getCommissionEarned($user)
    {
        // Calculate commission logic here
        return \App\Models\ReferLog::where('referrer_user_id', $user->id)
            ->sum('commission') ?? 0;
    }
    
    private function getTodaySales($user)
    {
        // Calculate today's sales logic here
        return TopUp::where('agent_id', $user->id)
            ->whereDate('created_at', today())
            ->sum('amount') ?? 0;
    }
}