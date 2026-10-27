<?php
// app/Console/Commands/AWCCheckIp.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AWCService;
use Illuminate\Support\Facades\Http;

class AWCCheckIp extends Command
{
    protected $signature = 'awc:check-ip';
    protected $description = 'Check current server IP for AWC whitelist';
    
    public function handle(AWCService $awcService)
    {
        $this->info('=== AWC IP Whitelist Check ===');
        $this->newLine();
        
        $ip = $awcService->getServerIpForWhitelist();
        $this->info("Current Server IP: {$ip}");
        
        $this->newLine();
        $this->info("To whitelist this IP, contact AWC support with:");
        $this->line("   IP: {$ip}");
        $this->line("   Agent ID: " . config('awc.agent_id'));
        $this->line("   Environment: " . config('app.env'));
        
        $this->newLine();
        
        // Test API call
        $this->info("Testing API call with this IP...");
        
        $response = $awcService->getPlatformList();
        
        if (isset($response['status'])) {
            if ($response['status'] === '0000') {
                $this->info("✅ API call successful! IP is whitelisted.");
            } elseif ($response['status'] === '1029') {
                $this->error("❌ API call failed: IP not whitelisted");
                $this->error("Message: " . ($response['desc'] ?? 'No description'));
            } else {
                $this->warn("⚠️ API call returned status: " . $response['status']);
                $this->warn("Message: " . ($response['desc'] ?? 'No description'));
            }
        } else {
            $this->error("❌ API call failed: Invalid response");
        }
    }
}