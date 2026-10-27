<?php

namespace App\Services;

use App\Models\BettingSlip;
use App\Models\BettingSelection;
use App\Models\BettingTransaction;
use App\Models\BettingSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BettingService
{
    protected $user;
    protected $settings;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->settings = $this->loadSettings();
    }

    protected function loadSettings()
    {
        return [
            'min_bet' => BettingSetting::getMinBetAmount(),
            'max_bet' => BettingSetting::getMaxBetAmount(),
            'max_multiple' => BettingSetting::getMaxMultipleBets(),
            'min_multiple' => BettingSetting::getMinMultipleBets(),
            'max_payout' => BettingSetting::getMaxPayout(),
        ];
    }

    public function placeSingleBet($match, $betMarket, $betSelection, $odds, $oddsValue, $stake)
    {
        if (!$this->user->canPlaceBet($stake)) {
            return [
                'success' => false,
                'message' => 'Cannot place bet. Insufficient balance or bet amount outside limits.'
            ];
        }

        if ($stake < $this->settings['min_bet'] || $stake > $this->settings['max_bet']) {
            return [
                'success' => false,
                'message' => "Bet amount must be between {$this->settings['min_bet']} and {$this->settings['max_bet']}"
            ];
        }

        $potentialWin = $stake * $odds;

        if ($potentialWin > $this->settings['max_payout']) {
            return [
                'success' => false,
                'message' => "Potential win exceeds maximum payout of {$this->settings['max_payout']}"
            ];
        }

        DB::beginTransaction();

        try {
            $balanceBefore = $this->user->balance;

            if (!$this->user->deductBalance($stake, null, "Single bet placed: {$match['host_team_name']} vs {$match['guest_team_name']} - {$betSelection}")) {
                throw new \Exception('Failed to deduct balance');
            }

            $slip = BettingSlip::create([
                'user_id' => $this->user->id,
                'bet_type' => 'single',
                'bet_category' => 'body',
                'total_stake' => $stake,
                'total_odds' => $odds,
                'potential_win' => $potentialWin,
                'status' => 'pending',
            ]);

            BettingSelection::create([
                'slip_id' => $slip->id,
                'slip_number' => $slip->slip_number,
                'user_id' => $this->user->id,
                'match_id' => $match['id'],
                'match_timestamp' => $match['fixture_timestamp'],
                'match_start_time' => date('Y-m-d H:i:s', $match['fixture_timestamp']),
                'host_team_name' => $match['host_team_name'],
                'guest_team_name' => $match['guest_team_name'],
                'league_name' => $match['league_name'],
                'bet_market' => $betMarket,
                'bet_selection' => $betSelection,
                'odds' => $odds,
                'odds_value' => $oddsValue,
            ]);

            BettingTransaction::create([
                'slip_id' => $slip->id,
                'slip_number' => $slip->slip_number,
                'user_id' => $this->user->id,
                'transaction_type' => 'bet_placed',
                'amount' => $stake,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->user->balance,
                'description' => "Single bet placed: {$match['host_team_name']} vs {$match['guest_team_name']} - {$betSelection} @ {$odds}",
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Bet placed successfully',
                'data' => [
                    'slip' => $slip,
                    'slip_number' => $slip->slip_number,
                    'total_stake' => $stake,
                    'total_odds' => $odds,
                    'potential_win' => $potentialWin,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Single bet placement failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to place bet: ' . $e->getMessage()
            ];
        }
    }

    public function placeMultiBet($selections, $stake)
    {
        if (count($selections) < $this->settings['min_multiple']) {
            return [
                'success' => false,
                'message' => "Multi bet requires at least {$this->settings['min_multiple']} selections"
            ];
        }

        if (count($selections) > $this->settings['max_multiple']) {
            return [
                'success' => false,
                'message' => "Multi bet cannot exceed {$this->settings['max_multiple']} selections"
            ];
        }

        if (!$this->user->canPlaceBet($stake)) {
            return [
                'success' => false,
                'message' => 'Cannot place bet. Insufficient balance or bet amount outside limits.'
            ];
        }

        if ($stake < $this->settings['min_bet'] || $stake > $this->settings['max_bet']) {
            return [
                'success' => false,
                'message' => "Bet amount must be between {$this->settings['min_bet']} and {$this->settings['max_bet']}"
            ];
        }

        $totalOdds = 1;
        foreach ($selections as $selection) {
            $totalOdds *= $selection['odds'];
        }

        $potentialWin = $stake * $totalOdds;

        if ($potentialWin > $this->settings['max_payout']) {
            return [
                'success' => false,
                'message' => "Potential win exceeds maximum payout of {$this->settings['max_payout']}"
            ];
        }

        DB::beginTransaction();

        try {
            $balanceBefore = $this->user->balance;

            if (!$this->user->deductBalance($stake, null, "Multi bet placed with " . count($selections) . " selections")) {
                throw new \Exception('Failed to deduct balance');
            }

            $slip = BettingSlip::create([
                'user_id' => $this->user->id,
                'bet_type' => 'multi',
                'bet_category' => 'maung',
                'total_stake' => $stake,
                'total_odds' => $totalOdds,
                'potential_win' => $potentialWin,
                'status' => 'pending',
            ]);

            foreach ($selections as $selection) {
                BettingSelection::create([
                    'slip_id' => $slip->id,
                    'slip_number' => $slip->slip_number,
                    'user_id' => $this->user->id,
                    'match_id' => $selection['match_id'],
                    'match_timestamp' => $selection['match_timestamp'],
                    'match_start_time' => date('Y-m-d H:i:s', $selection['match_timestamp']),
                    'host_team_name' => $selection['host_team_name'],
                    'guest_team_name' => $selection['guest_team_name'],
                    'league_name' => $selection['league_name'],
                    'bet_market' => $selection['bet_market'],
                    'bet_selection' => $selection['bet_selection'],
                    'odds' => $selection['odds'],
                    'odds_value' => $selection['odds_value'],
                ]);
            }

            BettingTransaction::create([
                'slip_id' => $slip->id,
                'slip_number' => $slip->slip_number,
                'user_id' => $this->user->id,
                'transaction_type' => 'bet_placed',
                'amount' => $stake,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->user->balance,
                'description' => "Multi bet placed with " . count($selections) . " selections @ {$totalOdds}",
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Multi bet placed successfully',
                'data' => [
                    'slip' => $slip,
                    'slip_number' => $slip->slip_number,
                    'total_selections' => count($selections),
                    'total_stake' => $stake,
                    'total_odds' => $totalOdds,
                    'potential_win' => $potentialWin,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Multi bet placement failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to place bet: ' . $e->getMessage()
            ];
        }
    }

    public function getBettingSlip($slipNumber)
    {
        $slip = BettingSlip::with('selections')
            ->where('slip_number', $slipNumber)
            ->where('user_id', $this->user->id)
            ->first();

        if (!$slip) {
            return [
                'success' => false,
                'message' => 'Betting slip not found'
            ];
        }

        return [
            'success' => true,
            'data' => $slip
        ];
    }

    public function getUserBets($status = null, $limit = 50)
    {
        $query = BettingSlip::with('selections')
            ->where('user_id', $this->user->id)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $bets = $query->paginate($limit);

        return [
            'success' => true,
            'data' => $bets
        ];
    }

    public function getBettingStatistics()
    {
        $stats = [
            'total_bets' => BettingSlip::where('user_id', $this->user->id)->count(),
            'pending_bets' => BettingSlip::where('user_id', $this->user->id)->where('status', 'pending')->count(),
            'won_bets' => BettingSlip::where('user_id', $this->user->id)->where('status', 'won')->count(),
            'lost_bets' => BettingSlip::where('user_id', $this->user->id)->where('status', 'lost')->count(),
            'total_staked' => BettingSlip::where('user_id', $this->user->id)->sum('total_stake'),
            'total_won' => BettingSlip::where('user_id', $this->user->id)->where('status', 'won')->sum('settled_amount'),
            'single_bets' => BettingSlip::where('user_id', $this->user->id)->where('bet_type', 'single')->count(),
            'multi_bets' => BettingSlip::where('user_id', $this->user->id)->where('bet_type', 'multi')->count(),
        ];

        return [
            'success' => true,
            'data' => $stats
        ];
    }
}