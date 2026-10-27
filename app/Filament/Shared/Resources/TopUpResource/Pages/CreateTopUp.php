<?php

namespace App\Filament\Shared\Resources\TopUpResource\Pages;

use App\Filament\Shared\Resources\TopUpResource;
use App\Models\UnitHistoryLog;
use App\Models\UserBalanceTransaction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateTopUp extends CreateRecord
{
    protected static string $resource = TopUpResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $agent = auth()->user();
        $targetUser = \App\Models\User::findOrFail($data['user_id']);
        $amount = $data['amount'];
        $status = $data['status'];

        if ($status != 1) {
            return static::getModel()::create($data);
        }

        if ($agent->balance < $amount) {
            Notification::make()
                ->title(__('Insufficient Balance'))
                ->body(__('You do not have enough balance to approve this deposit.'))
                ->danger()
                ->send();
            
            $this->halt();
        }

        return DB::transaction(function () use ($data, $agent, $targetUser, $amount) {
            $record = static::getModel()::create($data);

            UnitHistoryLog::create([
                'agent_id' => $agent->id,
                'user_id' => $targetUser->id,
                'amount' => $amount,
                'user_before_amount' => $targetUser->balance,
                'user_after_amount' => $targetUser->balance + $amount,
                'agent_before_amount' => $agent->balance,
                'agent_after_amount' => $agent->balance - $amount,
                'type' => 1, 
            ]);

            $agent->decrement('balance', $amount);
            $agent->decrement('withdraw_able', $amount);

            UserBalanceTransaction::create([
                'user_id' => $agent->id,
                'type' => '2',
                'amount' => $amount,
                'reason' => "{$targetUser->name} (ID: {$targetUser->id}) သို့ ငွေလွှဲပြောင်းပေးပို့ခြင်း",
                'comment' => $data['reason'] ?? 'Manual Deposit via Panel',
                'status' => '2',
                'which_type' => 'deposit_withdraw',
            ]);

            $targetUser->increment('balance', $amount);
            $targetUser->increment('withdraw_able', $amount);

            UserBalanceTransaction::create([
                'user_id' => $targetUser->id,
                'type' => '1',
                'amount' => $amount,
                'reason' => "{$agent->name} (ID: {$agent->id}) ထံမှ ငွေဖြည့်သွင်းမှု လက်ခံရရှိခြင်း",
                'comment' => $data['reason'] ?? 'Manual Deposit via Panel',
                'status' => '2', 
                'which_type' => 'deposit_withdraw',
            ]);

            return $record;
        });
    }
}