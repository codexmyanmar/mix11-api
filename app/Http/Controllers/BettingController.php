<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\FootballMatch;
use App\Models\BettingSlip;
use App\Models\BettingSetting;
use App\Models\BettingTransaction;
use App\Services\BettingService;
use Carbon\Carbon;

class BettingController extends Controller
{
    protected $bettingService;

    public function __construct(Request $request)
    {
        $user = $request->user();
        $this->bettingService = new BettingService($user);
    }

    /**
     * Get current Yangon time
     */
    private function getCurrentYangonTime()
    {
        return Carbon::now('Asia/Yangon');
    }

    /**
     * Check if bet can be placed before match start time
     */
    private function canPlaceBet($matchTimestamp, $matchStartTime)
    {
        $currentYangonTime = $this->getCurrentYangonTime();
        $matchDateTime = Carbon::parse($matchStartTime, 'Asia/Yangon');
        
        // Bet must be placed before match start time
        return $currentYangonTime->lt($matchDateTime);
    }

    /**
     * Place a single bet
     */
    public function placeSingleBet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'match_id' => 'required|string',
            'bet_market' => 'required|in:1x2_home,1x2_draw,1x2_away,over,under,odd,even,handicap_home,handicap_away,fh_home,fh_draw,fh_away,correct_score',
            'bet_selection' => 'required|string',
            'odds' => 'required|numeric|min:0.01',
            'odds_value' => 'required|string',
            'stake' => 'required|numeric|min:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $match = FootballMatch::find($request->match_id);
        
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found'
            ], 404);
        }

        // Check if bet can be placed (must be before match time in Yangon time)
        if (!$this->canPlaceBet($match->fixture_timestamp, $match->fixture_start_time)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot place bet. Match has already started or is completed.',
                'data' => [
                    'current_time' => $this->getCurrentYangonTime()->toDateTimeString(),
                    'match_start_time' => Carbon::parse($match->fixture_start_time, 'Asia/Yangon')->toDateTimeString(),
                    'timezone' => 'Asia/Yangon'
                ]
            ], 400);
        }

        $matchData = [
            'id' => $match->id,
            'fixture_timestamp' => $match->fixture_timestamp,
            'host_team_name' => $match->host_team_name_en,
            'guest_team_name' => $match->guest_team_name_en,
            'league_name' => $match->league_name_en,
        ];

        $result = $this->bettingService->placeSingleBet(
            $matchData,
            $request->bet_market,
            $request->bet_selection,
            $request->odds,
            $request->odds_value,
            $request->stake
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result, 200);
    }

    /**
     * Place a multi bet (Maung)
     */
    public function placeMultiBet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selections' => 'required|array|min:1|max:100',
            'selections.*.match_id' => 'required|string',
            'selections.*.bet_market' => 'required',
            'selections.*.bet_selection' => 'required|string',
            'selections.*.odds' => 'required|numeric|min:0.01',
            'selections.*.odds_value' => 'required|string',
            'stake' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $selections = [];
        $currentYangonTime = $this->getCurrentYangonTime();
        
        foreach ($request->selections as $index => $selection) {
            $match = FootballMatch::find($selection['match_id']);
            
            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => "Match not found for selection: {$selection['match_id']}"
                ], 404);
            }
            
            // Check if each bet can be placed (must be before match time in Yangon time)
            $matchDateTime = Carbon::parse($match->fixture_start_time, 'Asia/Yangon');
            if ($currentYangonTime->gte($matchDateTime)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot place bet. Match for selection {$index} has already started or is completed.",
                    'data' => [
                        'selection_index' => $index,
                        'match_id' => $match->id,
                        'current_time' => $currentYangonTime->toDateTimeString(),
                        'match_start_time' => $matchDateTime->toDateTimeString(),
                        'timezone' => 'Asia/Yangon'
                    ]
                ], 400);
            }
            
            $selections[] = [
                'match_id' => $match->id,
                'match_timestamp' => $match->fixture_timestamp,
                'host_team_name' => $match->host_team_name_en,
                'guest_team_name' => $match->guest_team_name_en,
                'league_name' => $match->league_name_en,
                'bet_market' => $selection['bet_market'],
                'bet_selection' => $selection['bet_selection'],
                'odds' => $selection['odds'],
                'odds_value' => $selection['odds_value'],
            ];
        }

        $result = $this->bettingService->placeMultiBet($selections, $request->stake);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result, 200);
    }

    /**
     * Get betting slip by number
     */
    public function getBettingSlip($slipNumber)
    {
        $result = $this->bettingService->getBettingSlip($slipNumber);

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result, 200);
    }

    /**
     * Get user's betting history
     */
    public function getUserBets(Request $request)
    {
        $status = $request->get('status');
        $limit = $request->get('limit', 50);
        
        $result = $this->bettingService->getUserBets($status, $limit);
        
        return response()->json($result, 200);
    }

    /**
     * Get betting statistics
     */
    public function getBettingStatistics()
    {
        $result = $this->bettingService->getBettingStatistics();
        
        return response()->json($result, 200);
    }

    /**
     * Cancel a pending bet
     */
    public function cancelBet($slipNumber)
    {
        $slip = BettingSlip::where('slip_number', $slipNumber)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        if (!$slip) {
            return response()->json([
                'success' => false,
                'message' => 'Bet not found or cannot be cancelled'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $user = auth()->user();
            $balanceBefore = $user->balance;

            $user->addBalance(
                $slip->total_stake,
                'refund',
                $slip->id,
                BettingSlip::class,
                "Bet cancellation refund for slip: {$slip->slip_number}"
            );

            $slip->markAsCancelled();

            BettingTransaction::create([
                'slip_id' => $slip->id,
                'slip_number' => $slip->slip_number,
                'user_id' => $user->id,
                'transaction_type' => 'refund',
                'amount' => $slip->total_stake,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'description' => "Bet cancelled: {$slip->slip_number}",
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bet cancelled successfully',
                'data' => [
                    'slip_number' => $slip->slip_number,
                    'refund_amount' => $slip->total_stake
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel bet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get betting limits and settings
     */
    public function getBettingLimits()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'min_bet' => BettingSetting::getMinBetAmount(),
                'max_bet' => BettingSetting::getMaxBetAmount(),
                'min_multiple_bets' => BettingSetting::getMinMultipleBets(),
                'max_multiple_bets' => BettingSetting::getMaxMultipleBets(),
                'max_payout' => BettingSetting::getMaxPayout(),
            ]
        ], 200);
    }

    /**
     * Calculate potential win for single bet
     */
    public function calculatePotentialWin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stake' => 'required|numeric|min:0',
            'odds' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $potentialWin = $request->stake * $request->odds;
        $maxPayout = BettingSetting::getMaxPayout();

        return response()->json([
            'success' => true,
            'data' => [
                'stake' => $request->stake,
                'odds' => $request->odds,
                'potential_win' => $potentialWin,
                'max_payout' => $maxPayout,
                'exceeds_limit' => $potentialWin > $maxPayout
            ]
        ], 200);
    }

    /**
     * Calculate potential win for multi bet
     */
    public function calculateMultiBetWin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stake' => 'required|numeric|min:0',
            'odds' => 'required|array|min:2',
            'odds.*' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $totalOdds = array_product($request->odds);
        $potentialWin = $request->stake * $totalOdds;
        $maxPayout = BettingSetting::getMaxPayout();

        return response()->json([
            'success' => true,
            'data' => [
                'stake' => $request->stake,
                'individual_odds' => $request->odds,
                'total_odds' => $totalOdds,
                'potential_win' => $potentialWin,
                'max_payout' => $maxPayout,
                'exceeds_limit' => $potentialWin > $maxPayout
            ]
        ], 200);
    }
}