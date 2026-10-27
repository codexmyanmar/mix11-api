<?php

namespace App\Filament\Shared\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\User;
use App\Models\TopUp;
use App\Models\Withdraw;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserStatisticsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = $this->getStatistics();

        return [
            Stat::make('စုစုပေါင်း အသုံးပြုသူ', number_format($stats['total_users']))
                ->description('သင်ဖိတ်ခေါ်ထားသော လူဦးရေ')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('တက်ကြွသူ (Active)', number_format($stats['active_users']))
                ->description('လက်ရှိကစားနေသော အသုံးပြုသူများ')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('ပိတ်ထားသူ (Banned)', number_format($stats['banned_users']))
                ->description('အကောင့်ပိတ်ထားခံရသူများ')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('စုစုပေါင်း လက်ကျန်ငွေ', number_format($stats['total_balance'], 2) . ' THB')
                ->description('အသုံးပြုသူများ၏ ပိုက်ဆံအိတ်ထဲရှိငွေ')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('warning'),

            Stat::make('စုစုပေါင်း ဖြည့်သွင်းငွေ', number_format($stats['total_topup'], 2) . ' THB')
                ->description('အောင်မြင်သော ဖြည့်သွင်းငွေစုစုပေါင်း')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make('စုစုပေါင်း ထုတ်ယူငွေ', number_format($stats['total_withdraw'], 2) . ' THB')
                ->description('အောင်မြင်သော ထုတ်ယူငွေစုစုပေါင်း')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('rose'),
        ];
    }

    protected function getStatistics(): array
    {
        $authId = Auth::id();
        
        // Page Filters ယူခြင်း
        $dateFilter = $this->filters['dateRange'] ?? 'all';
        $statusFilter = $this->filters['userStatus'] ?? 'all';
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        $referredUserIds = DB::table('refer_logs')
            ->where('referrer_user_id', $authId)
            ->pluck('referred_user_id');

        $userQuery = User::whereIn('id', $referredUserIds);
        $topupQuery = TopUp::whereIn('user_id', $referredUserIds)->where('status', TopUp::STATUS_COMPLETE);
        $withdrawQuery = Withdraw::whereIn('user_id', $referredUserIds)->where('status', Withdraw::STATUS_COMPLETE);

        // ၁။ User Status Filter (0 = banned, 1 = active လို့ ယူဆထားပါတယ်)
        if ($statusFilter === 'active') {
            $userQuery->where('status', 1);
        } elseif ($statusFilter === 'banned') {
            $userQuery->where('status', 0);
        }

        // ၂။ Date Filter Logic
        if ($dateFilter !== 'all') {
            $range = match ($dateFilter) {
                'today' => [now()->startOfDay(), now()->endOfDay()],
                'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
                'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
                'this_year' => [now()->startOfYear(), now()->endOfYear()],
                'custom' => ($startDate && $endDate) ? [$startDate, $endDate] : null,
                default => null,
            };

            if ($range) {
                $userQuery->whereBetween('created_at', $range);
                $topupQuery->whereBetween('created_at', $range);
                $withdrawQuery->whereBetween('created_at', $range);
            }
        }

        return [
            'total_users' => $userQuery->count(),
            'active_users' => (clone $userQuery)->where('status', 1)->count(),
            'banned_users' => (clone $userQuery)->where('status', 0)->count(),
            'total_balance' => (clone $userQuery)->sum('balance'),
            'total_topup' => $topupQuery->sum('amount'),
            'total_withdraw' => $withdrawQuery->sum('amount'),
        ];
    }
}