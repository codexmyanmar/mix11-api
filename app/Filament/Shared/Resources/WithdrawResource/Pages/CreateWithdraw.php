<?php

namespace App\Filament\Shared\Resources\WithdrawResource\Pages;

use App\Filament\Shared\Resources\WithdrawResource;
use App\Models\UnitHistoryLog;
use App\Models\UserBalanceTransaction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateWithdraw extends CreateRecord
{
    protected static string $resource = WithdrawResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $agent = auth()->user();
        $targetUser = \App\Models\User::findOrFail($data['user_id']);
        $amount = $data['amount'];
        $status = (int) $data['status'];

        // 1. Check User Balance First (For both Pending and Complete)
        if ($targetUser->balance < $amount) {
            Notification::make()
                ->title(__('Insufficient User Balance'))
                ->body(__("The user {$targetUser->name} does not have enough balance for this withdrawal."))
                ->danger()
                ->send();
            
            $this->halt();
        }

        return DB::transaction(function () use ($data, $agent, $targetUser, $amount, $status) {
            $record = static::getModel()::create($data);

            // 2. Reduce User Balance (Pending Action) - Happens for Pending AND Complete
            $targetUser->decrement('balance', $amount);
            $targetUser->decrement('withdraw_able', $amount);

            UserBalanceTransaction::create([
                'user_id' => $targetUser->id,
                'type' => 2, // Reduce
                'amount' => $amount,
                'reason' => "Withdraw Request (ID: {$record->id})",
                'comment' => $data['reason'] ?? 'Manual Withdraw via Panel',
                'status' => 2,
                'which_type' => 'deposit_withdraw',
            ]);

            // 3. If Complete, Agent gets units immediately
            if ($status === 1) {
                UnitHistoryLog::create([
                    'agent_id' => $agent->id,
                    'user_id' => $targetUser->id,
                    'amount' => $amount,
                    'user_before_amount' => $targetUser->balance + $amount, // Before the decrement above
                    'user_after_amount' => $targetUser->balance,
                    'agent_before_amount' => $agent->balance,
                    'agent_after_amount' => $agent->balance + $amount,
                    'type' => 2, // Withdraw
                ]);

                $agent->increment('balance', $amount);
                // $agent->increment('withdraw_able', $amount);

                UserBalanceTransaction::create([
                    'user_id' => $agent->id,
                    'type' => 1, // Add
                    'amount' => $amount,
                    'reason' => "Withdraw processed for {$targetUser->name}",
                    'comment' => $data['reason'] ?? 'Manual Withdraw Complete',
                    'status' => 2,
                    'which_type' => 'deposit_withdraw',
                ]);
            }

            return $record;
        });
    }
}