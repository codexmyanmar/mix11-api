<?php

namespace App\Filament\Shared\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GameBettingStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.shared.pages.game-betting-status';
    protected static ?string $navigationLabel = 'ဂိမ်းလောင်းကြေးအခြေအနေ';
    protected static ?string $navigationGroup = 'အစီရင်ခံစာများ';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = true;
    
    // Hide the page title
    protected static ?string $title = '';
    
    public $start_date;
    public $end_date;
    
    public function mount()
    {
        // Set default dates to show data that exists
        $awcMaxDate = DB::table('awc_transactions')->max('bet_time');
        $boleMaxDate = DB::table('bole_transactions')->max('created_at');
        $kingkongMaxDate = DB::table('kingkong_transactions')->max('created_at');
        $live22MaxDate = DB::table('live22_processed_transactions')->max('tran_date_time');
        $jokerMaxDate = DB::table('joker_transactions')->max('created_at');
        $rich88MaxDate = DB::table('rich88_transfers')->max('created_at');
        $buffaloMaxDate = DB::table('buffalo_transactions')->max('created_at');
        
        $maxDate = max(
            $awcMaxDate ? strtotime($awcMaxDate) : 0,
            $boleMaxDate ? strtotime($boleMaxDate) : 0,
            $kingkongMaxDate ? strtotime($kingkongMaxDate) : 0,
            $live22MaxDate ? strtotime($live22MaxDate) : 0,
            $jokerMaxDate ? strtotime($jokerMaxDate) : 0,
            $rich88MaxDate ? strtotime($rich88MaxDate) : 0,
            $buffaloMaxDate ? strtotime($buffaloMaxDate) : 0
        );
        
        if ($maxDate > 0) {
            $this->start_date = date('Y-m-d', $maxDate);
            $this->end_date = date('Y-m-d', $maxDate);
        } else {
            $this->start_date = now()->toDateString();
            $this->end_date = now()->toDateString();
        }
    }
    
    /**
     * Get all end user IDs (players) under the current user's hierarchy
     * This excludes the parent/super/master/agent accounts themselves
     */
    protected function getAllowedUserIds()
    {
        $currentUser = Auth::user();
        
        // If user is admin, return null to show all data (no filtering)
        if ($currentUser->role === 'admin') {
            return null;
        }
        
        // Get all end users (players) under this user's hierarchy
        // We need to find users who have no one referred under them (leaf nodes)
        // OR specifically users who are 'user' role
        $allUserIds = [];
        
        // Start with users directly referred by the current user
        $this->getAllReferredUsers($currentUser->id, $allUserIds);
        
        // Remove duplicates
        $allUserIds = array_unique($allUserIds);
        
        // Now filter to only include end users (players)
        // Get users who have role 'user' OR who don't have any referred users
        $endUserIds = DB::table('users')
            ->whereIn('id', $allUserIds)
            ->where(function($query) {
                $query->where('role', 'user')
                      ->orWhereNotIn('id', function($subquery) {
                          $subquery->select('referrer_user_id')
                                   ->from('refer_logs')
                                   ->distinct();
                      });
            })
            ->pluck('id')
            ->toArray();
        
        // Also include the current user only if they have role 'user' (are a player)
        if ($currentUser->role === 'user') {
            $endUserIds[] = $currentUser->id;
        }
        
        return array_unique($endUserIds);
    }
    
    /**
     * Get all referred users recursively (including all levels)
     */
    protected function getAllReferredUsers($referrerId, &$userIds)
    {
        $referredUsers = DB::table('refer_logs')
            ->where('referrer_user_id', $referrerId)
            ->pluck('referred_user_id')
            ->toArray();
        
        foreach ($referredUsers as $referredUserId) {
            if (!in_array($referredUserId, $userIds)) {
                $userIds[] = $referredUserId;
                // Recursively get users referred by this user
                $this->getAllReferredUsers($referredUserId, $userIds);
            }
        }
    }
    
    /**
     * Get all user IDs under hierarchy including intermediate users (for filtering)
     * This includes masters, agents, and all downline users
     */
    protected function getAllUserIdsInHierarchy()
    {
        $currentUser = Auth::user();
        
        // If user is admin, return null to show all data
        if ($currentUser->role === 'admin') {
            return null;
        }
        
        // Get all users under hierarchy including the current user if they are a player
        $allUserIds = [];
        
        // Add the current user only if they have role 'user' (are a player)
        if ($currentUser->role === 'user') {
            $allUserIds[] = $currentUser->id;
        }
        
        // Get all referred users recursively
        $this->getAllReferredUsers($currentUser->id, $allUserIds);
        
        return array_unique($allUserIds);
    }
    
    public function getSummaryData()
    {
        $allowedUserIds = $this->getAllUserIdsInHierarchy();
        
        // Get AWC transactions data
        $awcQuery = DB::table('awc_transactions')
            ->select(
                'platform as provider',
                DB::raw('COALESCE(SUM(bet_amount), 0) as total_bet'),
                DB::raw('COALESCE(SUM(win_amount), 0) as total_user_win'),
                DB::raw('COALESCE(SUM(bet_amount - win_amount), 0) as net_profit')
            )
            ->where('status', 'settle')
            ->whereDate('bet_time', '>=', $this->start_date)
            ->whereDate('bet_time', '<=', $this->end_date);
        
        // Apply user filter for AWC if needed
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $awcQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $awcData = $awcQuery->groupBy('platform')->get()->toArray();
        
        // Get Bole Gaming transactions data
        $boleQuery = DB::table('bole_transactions')
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $boleQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $boleTransactions = $boleQuery->get();
        
        $boleTotalBet = 0;
        $boleTotalUserWin = 0;
        
        foreach ($boleTransactions as $transaction) {
            $metadata = json_decode($transaction->metadata, true);
            
            // Check for bet transactions
            $isBet = false;
            if ($metadata && isset($metadata['type'])) {
                if ($metadata['type'] == 20 || $metadata['type'] == 'bet' || $metadata['type'] == 1) {
                    $isBet = true;
                }
            }
            if ($transaction->type == 'bet' || $transaction->type == 'place_bet') {
                $isBet = true;
            }
            
            if ($isBet && $metadata && isset($metadata['amount'])) {
                $betAmount = floatval($metadata['amount']);
                if ($betAmount < 0) {
                    $betAmount = abs($betAmount);
                }
                $boleTotalBet += $betAmount;
            }
            
            // Check for win transactions
            $isWin = false;
            if ($transaction->type == 'win') {
                $isWin = true;
            }
            if ($metadata && isset($metadata['type'])) {
                if ($metadata['type'] == 21 || $metadata['type'] == 'win' || $metadata['type'] == 2) {
                    $isWin = true;
                }
            }
            
            if ($isWin) {
                $winAmount = floatval($transaction->amount);
                if ($winAmount == 0 && $metadata && isset($metadata['amount']) && $metadata['amount'] > 0) {
                    $winAmount = floatval($metadata['amount']);
                }
                $boleTotalUserWin += $winAmount;
            }
        }
        
        $boleData = null;
        if ($boleTotalBet > 0 || $boleTotalUserWin > 0) {
            $boleData = (object)[
                'provider' => 'bolegaming',
                'total_bet' => $boleTotalBet,
                'total_user_win' => $boleTotalUserWin,
                'net_profit' => $boleTotalBet - $boleTotalUserWin
            ];
        }
        
        // Get King Kong transactions data
        $kingkongQuery = DB::table('kingkong_transactions')
            ->select(
                DB::raw("'kingkong' as provider"),
                DB::raw('COALESCE(SUM(bet_amount), 0) as total_bet'),
                DB::raw('COALESCE(SUM(COALESCE(win_amount, 0)), 0) as total_user_win'),
                DB::raw('COALESCE(SUM(bet_amount - COALESCE(win_amount, 0)), 0) as net_profit')
            )
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $kingkongQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $kingkongData = $kingkongQuery->groupBy('provider')->first();
        
        // Get Live22 transactions data
        $live22Query = DB::table('live22_processed_transactions')
            ->select(
                DB::raw("'live22' as provider"),
                DB::raw('COALESCE(SUM(bet_amount), 0) as total_bet'),
                DB::raw('COALESCE(SUM(payout), 0) as total_user_win'),
                DB::raw('COALESCE(SUM(payout - bet_amount), 0) as net_profit')
            )
            ->whereDate('tran_date_time', '>=', $this->start_date)
            ->whereDate('tran_date_time', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $live22Query->whereIn('user_id', $allowedUserIds);
        }
        
        $live22Data = $live22Query->groupBy('provider')->first();
        
        // Get Joker transactions data
        $jokerBetsQuery = DB::table('joker_transactions')
            ->where('type', 'bet')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $jokerBetsQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $jokerBets = $jokerBetsQuery->get();
        
        $jokerTotalBet = 0;
        foreach ($jokerBets as $bet) {
            $jokerTotalBet += abs(floatval($bet->amount));
        }
        
        $jokerWinQuery = DB::table('joker_transactions')
            ->where('type', 'settle')
            ->where('amount', '>', 0)
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $jokerWinQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $jokerTotalUserWin = $jokerWinQuery->sum('amount');
        
        $jokerBalanceQuery = DB::table('joker_transactions')
            ->select(DB::raw('SUM(CASE WHEN new_balance > old_balance THEN (new_balance - old_balance) ELSE 0 END) as total_win'))
            ->where('type', 'settle')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $jokerBalanceQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $jokerBalanceWins = $jokerBalanceQuery->first();
        $jokerTotalUserWin = max($jokerTotalUserWin, $jokerBalanceWins->total_win ?? 0);
        
        $jokerData = null;
        if ($jokerTotalBet > 0 || $jokerTotalUserWin > 0) {
            $jokerData = (object)[
                'provider' => 'joker',
                'total_bet' => $jokerTotalBet,
                'total_user_win' => $jokerTotalUserWin,
                'net_profit' => $jokerTotalBet - $jokerTotalUserWin
            ];
        }
        
        // Get Rich88 transactions data
        $rich88BetQuery = DB::table('rich88_transfers')
            ->where('transfer_type', 'withdraw')
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $rich88BetQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $rich88TotalBet = $rich88BetQuery->sum('amount');
        
        $rich88WinQuery = DB::table('rich88_transfers')
            ->where('transfer_type', 'deposit')
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $rich88WinQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $rich88TotalUserWin = $rich88WinQuery->sum('amount');
        
        $rich88Data = null;
        if ($rich88TotalBet > 0 || $rich88TotalUserWin > 0) {
            $rich88Data = (object)[
                'provider' => 'rich88',
                'total_bet' => $rich88TotalBet,
                'total_user_win' => $rich88TotalUserWin,
                'net_profit' => $rich88TotalBet - $rich88TotalUserWin
            ];
        }
        
        // Get Buffalo transactions data
        $buffaloQuery = DB::table('buffalo_transactions')
            ->where('status', 'success')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            // Buffalo uses 'uid' column with format "uid_{user_id}"
            $allowedUserIdsFormatted = array_map(function($id) {
                return 'uid_' . $id;
            }, $allowedUserIds);
            $buffaloQuery->whereIn('uid', $allowedUserIdsFormatted);
        }
        
        $buffaloTotalBet = $buffaloQuery->sum('bet') / 100;
        $buffaloTotalUserWin = $buffaloQuery->sum('win') / 100;
        
        $buffaloData = null;
        if ($buffaloTotalBet > 0 || $buffaloTotalUserWin > 0) {
            $buffaloData = (object)[
                'provider' => 'buffalo',
                'total_bet' => $buffaloTotalBet,
                'total_user_win' => $buffaloTotalUserWin,
                'net_profit' => $buffaloTotalBet - $buffaloTotalUserWin
            ];
        }
        
        // Merge all datasets
        $results = collect($awcData);
        
        if ($boleData) {
            $results->push($boleData);
        }
        
        if ($kingkongData && ($kingkongData->total_bet > 0 || $kingkongData->total_user_win > 0)) {
            $results->push($kingkongData);
        }
        
        if ($live22Data && ($live22Data->total_bet > 0 || $live22Data->total_user_win > 0)) {
            $results->push($live22Data);
        }
        
        if ($jokerData) {
            $results->push($jokerData);
        }
        
        if ($rich88Data) {
            $results->push($rich88Data);
        }
        
        if ($buffaloData) {
            $results->push($buffaloData);
        }
        
        return $results->sortBy('provider')->values();
    }
    
    public function getTotalSummary()
    {
        $allowedUserIds = $this->getAllUserIdsInHierarchy();
        
        // Get AWC totals
        $awcQuery = DB::table('awc_transactions')
            ->select(
                DB::raw('COALESCE(SUM(bet_amount), 0) as total_bet'),
                DB::raw('COALESCE(SUM(win_amount), 0) as total_user_win'),
                DB::raw('COALESCE(SUM(bet_amount - win_amount), 0) as net_profit')
            )
            ->where('status', 'settle')
            ->whereDate('bet_time', '>=', $this->start_date)
            ->whereDate('bet_time', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $awcQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $awcTotals = $awcQuery->first();
        
        // Get Bole Gaming totals
        $boleQuery = DB::table('bole_transactions')
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $boleQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $boleTransactions = $boleQuery->get();
        
        $boleTotalBet = 0;
        $boleTotalUserWin = 0;
        
        foreach ($boleTransactions as $transaction) {
            $metadata = json_decode($transaction->metadata, true);
            
            $isBet = false;
            if ($metadata && isset($metadata['type'])) {
                if ($metadata['type'] == 20 || $metadata['type'] == 'bet' || $metadata['type'] == 1) {
                    $isBet = true;
                }
            }
            if ($transaction->type == 'bet' || $transaction->type == 'place_bet') {
                $isBet = true;
            }
            
            if ($isBet && $metadata && isset($metadata['amount'])) {
                $betAmount = floatval($metadata['amount']);
                if ($betAmount < 0) {
                    $betAmount = abs($betAmount);
                }
                $boleTotalBet += $betAmount;
            }
            
            $isWin = false;
            if ($transaction->type == 'win') {
                $isWin = true;
            }
            if ($metadata && isset($metadata['type'])) {
                if ($metadata['type'] == 21 || $metadata['type'] == 'win' || $metadata['type'] == 2) {
                    $isWin = true;
                }
            }
            
            if ($isWin) {
                $winAmount = floatval($transaction->amount);
                if ($winAmount == 0 && $metadata && isset($metadata['amount']) && $metadata['amount'] > 0) {
                    $winAmount = floatval($metadata['amount']);
                }
                $boleTotalUserWin += $winAmount;
            }
        }
        
        // Get King Kong totals
        $kingkongQuery = DB::table('kingkong_transactions')
            ->select(
                DB::raw('COALESCE(SUM(bet_amount), 0) as total_bet'),
                DB::raw('COALESCE(SUM(COALESCE(win_amount, 0)), 0) as total_user_win'),
                DB::raw('COALESCE(SUM(bet_amount - COALESCE(win_amount, 0)), 0) as net_profit')
            )
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $kingkongQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $kingkongTotals = $kingkongQuery->first();
        
        // Get Live22 totals
        $live22Query = DB::table('live22_processed_transactions')
            ->select(
                DB::raw('COALESCE(SUM(bet_amount), 0) as total_bet'),
                DB::raw('COALESCE(SUM(payout), 0) as total_user_win'),
                DB::raw('COALESCE(SUM(payout - bet_amount), 0) as net_profit')
            )
            ->whereDate('tran_date_time', '>=', $this->start_date)
            ->whereDate('tran_date_time', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $live22Query->whereIn('user_id', $allowedUserIds);
        }
        
        $live22Totals = $live22Query->first();
        
        // Get Joker totals
        $jokerBetsQuery = DB::table('joker_transactions')
            ->where('type', 'bet')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $jokerBetsQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $jokerBets = $jokerBetsQuery->get();
        
        $jokerTotalBet = 0;
        foreach ($jokerBets as $bet) {
            $jokerTotalBet += abs(floatval($bet->amount));
        }
        
        $jokerWinQuery = DB::table('joker_transactions')
            ->where('type', 'settle')
            ->where('amount', '>', 0)
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $jokerWinQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $jokerTotalUserWin = $jokerWinQuery->sum('amount');
        
        $jokerBalanceQuery = DB::table('joker_transactions')
            ->select(DB::raw('SUM(CASE WHEN new_balance > old_balance THEN (new_balance - old_balance) ELSE 0 END) as total_win'))
            ->where('type', 'settle')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $jokerBalanceQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $jokerBalanceWins = $jokerBalanceQuery->first();
        $jokerTotalUserWin = max($jokerTotalUserWin, $jokerBalanceWins->total_win ?? 0);
        $jokerNetProfit = $jokerTotalBet - $jokerTotalUserWin;
        
        // Get Rich88 totals
        $rich88BetQuery = DB::table('rich88_transfers')
            ->where('transfer_type', 'withdraw')
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $rich88BetQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $rich88TotalBet = $rich88BetQuery->sum('amount');
        
        $rich88WinQuery = DB::table('rich88_transfers')
            ->where('transfer_type', 'deposit')
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $rich88WinQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $rich88TotalUserWin = $rich88WinQuery->sum('amount');
        $rich88NetProfit = $rich88TotalBet - $rich88TotalUserWin;
        
        // Get Buffalo totals
        $buffaloQuery = DB::table('buffalo_transactions')
            ->where('status', 'success')
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
        
        if ($allowedUserIds !== null && !empty($allowedUserIds)) {
            $allowedUserIdsFormatted = array_map(function($id) {
                return 'uid_' . $id;
            }, $allowedUserIds);
            $buffaloQuery->whereIn('uid', $allowedUserIdsFormatted);
        }
        
        $buffaloTotalBet = $buffaloQuery->sum('bet') / 100;
        $buffaloTotalUserWin = $buffaloQuery->sum('win') / 100;
        $buffaloNetProfit = $buffaloTotalBet - $buffaloTotalUserWin;
        
        // Calculate Bole net profit
        $boleNetProfit = $boleTotalBet - $boleTotalUserWin;
        
        // Combine totals
        return (object)[
            'total_bet' => ($awcTotals->total_bet ?? 0) + $boleTotalBet + ($kingkongTotals->total_bet ?? 0) + ($live22Totals->total_bet ?? 0) + $jokerTotalBet + $rich88TotalBet + $buffaloTotalBet,
            'total_user_win' => ($awcTotals->total_user_win ?? 0) + $boleTotalUserWin + ($kingkongTotals->total_user_win ?? 0) + ($live22Totals->total_user_win ?? 0) + $jokerTotalUserWin + $rich88TotalUserWin + $buffaloTotalUserWin,
            'net_profit' => ($awcTotals->net_profit ?? 0) + $boleNetProfit + ($kingkongTotals->net_profit ?? 0) + ($live22Totals->net_profit ?? 0) + $jokerNetProfit + $rich88NetProfit + $buffaloNetProfit
        ];
    }
}