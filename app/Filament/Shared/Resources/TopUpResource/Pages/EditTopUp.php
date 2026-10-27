<?php

namespace App\Filament\Shared\Resources\TopUpResource\Pages;

use App\Filament\Shared\Resources\TopUpResource;
use App\Models\UnitHistoryLog;
use App\Models\UserBalanceTransaction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditTopUp extends EditRecord
{
    protected static string $resource = TopUpResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldStatus = $record->status;
        $newStatus = $data['status'];
        $amount = $record->amount;

        if ($newStatus == 1 && $oldStatus != 1) {
            
            $agent = auth()->user();
            $targetUser = \App\Models\User::findOrFail($record->user_id);

            if ($agent->balance < $amount) {
                Notification::make()
                    ->title(__('Insufficient Balance'))
                    ->body(__('You do not have enough balance to approve this deposit.'))
                    ->danger()
                    ->send();
                
                $this->halt();
            }

            DB::transaction(function () use ($record, $data, $agent, $targetUser, $amount) {
                $record->update($data);

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
                    'type' => 'reduce',
                    'amount' => $amount,
                    'reason' => "{$targetUser->name} (ID: {$targetUser->id}) သို့ ငွေလွှဲပြောင်းပေးပို့ခြင်း",
                    'comment' => $data['reason'] ?? 'Deposit Approved',
                    'status' => 'completed',
                    'which_type' => 'deposit_withdraw',
                ]);

                $targetUser->increment('balance', $amount);
                $targetUser->increment('withdraw_able', $amount);

                UserBalanceTransaction::create([
                    'user_id' => $targetUser->id,
                    'type' => 'add',
                    'amount' => $amount,
                    'reason' => "{$agent->name} (ID: {$agent->id}) ထံမှ ငွေဖြည့်သွင်းမှု လက်ခံရရှိခြင်း",
                    'comment' => $data['reason'] ?? 'Deposit Approved',
                    'status' => 'completed',
                    'which_type' => 'deposit_withdraw',
                ]);
            });

            return $record;
        }

        $record->update($data);
        return $record;
    }
}