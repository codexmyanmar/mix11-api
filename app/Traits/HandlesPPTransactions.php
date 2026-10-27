<?php

namespace App\Traits;

use App\Models\PPRound;
use App\Models\PPTransaction;
use Illuminate\Support\Facades\DB;

trait HandlesPPTransactions
{
    /**
     * Record a bet transaction
     */
    protected function recordBet($userId, $gameId, $roundId, $amount, $reference, $oldBalance, $newBalance)
    {
        return PPTransaction::create([
            'reference' => $reference,
            'transaction_id' => 'BET_' . time() . '_' . uniqid(),
            'user_id' => $userId,
            'type' => 'bet',
            'amount' => $amount,
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'currency' => 'THB',
            'game_id' => $gameId,
            'round_id' => $roundId,
            'status' => 1
        ]);
    }
    
    /**
     * Update or create round
     */
    protected function updateRound($userId, $gameId, $roundId, $betAmount = 0, $winAmount = 0)
    {
        $round = PPRound::firstOrCreate(
            ['round_id' => $roundId, 'user_id' => $userId],
            [
                'game_id' => $gameId,
                'status' => 'in_progress',
                'started_at' => now(),
                'total_bet' => 0,
                'total_win' => 0,
                'net_result' => 0
            ]
        );
        
        if ($betAmount > 0) {
            $round->total_bet += $betAmount;
            $round->net_result -= $betAmount;
        }
        
        if ($winAmount > 0) {
            $round->total_win += $winAmount;
            $round->net_result += $winAmount;
        }
        
        $round->save();
        
        return $round;
    }
    
    /**
     * Complete a round
     */
    protected function completeRound($roundId, $userId, $endBalance = null)
    {
        return PPRound::where('round_id', $roundId)
            ->where('user_id', $userId)
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
                'end_balance' => $endBalance
            ]);
    }
}