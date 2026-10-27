<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\BoleTransaction;
use App\Services\BoleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncBoleGameLogs extends Command
{
    protected $signature = 'bole:sync-logs {--hours=24 : Number of hours to look back}';
    protected $description = 'Sync Bole game logs and update user balances';

    protected $boleService;

    public function __construct(BoleService $boleService)
    {
        parent::__construct();
        $this->boleService = $boleService;
    }

    public function handle()
    {
        $hours = $this->option('hours');
        $endTime = time();
        $startTime = $endTime - ($hours * 3600);

        $this->info("Syncing Bole game logs from " . date('Y-m-d H:i:s', $startTime) . " to " . date('Y-m-d H:i:s', $endTime));

        // Get contest game records
        $response = $this->boleService->getContestGameRecords($startTime, $endTime);
        
        if (isset($response['resp_msg']['code']) && $response['resp_msg']['code'] == 200) {
            $records = $response['resp_data']['data'] ?? [];
            $this->info("Found " . count($records) . " contest records");
            
            foreach ($records as $record) {
                $this->processGameRecord($record);
            }
        }

        // Get slot game records
        $slotResponse = $this->boleService->getSlotGameRecords($startTime, $endTime);
        
        if (isset($slotResponse['resp_msg']['code']) && $slotResponse['resp_msg']['code'] == 200) {
            $slotRecords = $slotResponse['resp_data']['data'] ?? [];
            $this->info("Found " . count($slotRecords) . " slot records");
            
            foreach ($slotRecords as $record) {
                $this->processGameRecord($record);
            }
        }

        $this->info("Sync completed!");
    }

    protected function processGameRecord($record)
    {
        $playerAccount = $record['player_account'] ?? null;
        $gainGold = floatval($record['gain_gold'] ?? 0);
        $betNum = floatval($record['bet_num'] ?? 0);
        $reportId = $record['report_id'] ?? null;
        
        if (!$playerAccount) {
            return;
        }

        $user = User::where('user_id', $playerAccount)->first();
        
        if (!$user) {
            return;
        }

        // Check if already processed
        $existing = BoleTransaction::where('transaction_id', $reportId)->first();
        if ($existing) {
            return;
        }

        // Update balance if needed
        if ($gainGold != 0) {
            DB::beginTransaction();
            try {
                $oldBalance = $user->balance;
                
                if ($gainGold > 0) {
                    // Win
                    $user->balance += $gainGold;
                    $type = 'win';
                } else {
                    // Loss
                    $user->balance += $gainGold; // gainGold is negative
                    $type = 'bet';
                }
                
                $user->save();

                BoleTransaction::create([
                    'user_id' => $user->id,
                    'transaction_id' => $reportId,
                    'order_id' => null,
                    'type' => $type,
                    'amount' => abs($gainGold),
                    'balance_before' => $oldBalance,
                    'balance_after' => $user->balance,
                    'status' => 'synced',
                    'metadata' => json_encode($record)
                ]);

                DB::commit();
                
                $this->info("Processed record for {$playerAccount}: gain={$gainGold}, new_balance={$user->balance}");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error processing record: " . $e->getMessage());
            }
        }
    }
}