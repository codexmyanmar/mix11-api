<?php
// app/Console/Commands/AWCTestSimple.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AWCService;
use Illuminate\Support\Facades\Http;

class AWCTestSimple extends Command
{
    protected $signature = 'awc:test-simple';
    protected $description = 'Simple test for AWC API';
    
    public function handle(AWCService $awcService)
    {
        $this->info('=== AWC Simple Test ===');
        $this->newLine();
        
        // Show configuration
        $this->line('Configuration:');
        $this->line('  API URL: ' . config('awc.api_url'));
        $this->line('  Agent ID: ' . config('awc.agent_id'));
        $this->line('  Server IP: ' . $awcService->getServerIpForWhitelist());
        $this->newLine();
        
        // Test 1: Get Platform List
        $this->info('Test 1: Get Platform List');
        $result = $awcService->getPlatformList();
        
        if (isset($result['status'])) {
            $this->line('  Status: ' . $result['status']);
            $this->line('  Message: ' . ($result['desc'] ?? 'No message'));
            
            if ($result['status'] === '0000') {
                $this->info('  ✓ Success!');
                if (isset($result['platforms'])) {
                    $this->line('  Platforms: ' . json_encode($result['platforms']));
                }
            } elseif ($result['status'] === '1029') {
                $this->error('  ✗ IP NOT WHITELISTED!');
                $this->error('  Please whitelist IP: ' . $awcService->getServerIpForWhitelist());
            } else {
                $this->error('  ✗ Failed: ' . ($result['desc'] ?? 'Unknown error'));
            }
        } else {
            $this->error('  ✗ Invalid response: ' . json_encode($result));
        }
        
        $this->newLine();
        
        // Test 2: Create Test Member
        $this->info('Test 2: Create Test Member');
        $testUserId = 'test_' . time();
        $result = $awcService->createMember($testUserId, 'MMK', 'TestUser');
        
        if (isset($result['status'])) {
            $this->line('  User ID: ' . $testUserId);
            $this->line('  Status: ' . $result['status']);
            $this->line('  Message: ' . ($result['desc'] ?? 'No message'));
            
            if ($result['status'] === '0000') {
                $this->info('  ✓ Member created!');
            } elseif ($result['status'] === '1029') {
                $this->error('  ✗ IP not whitelisted');
            } else {
                $this->error('  ✗ Failed: ' . ($result['desc'] ?? 'Unknown error'));
            }
        }
        
        $this->newLine();
        
        // Test 3: Login
        if (isset($result['status']) && $result['status'] === '0000') {
            $this->info('Test 3: Login');
            $loginResult = $awcService->login($testUserId);
            
            if (isset($loginResult['status'])) {
                $this->line('  Status: ' . $loginResult['status']);
                if ($loginResult['status'] === '0000' && isset($loginResult['url'])) {
                    $this->info('  ✓ Game URL received!');
                    $this->line('  URL: ' . $loginResult['url']);
                } else {
                    $this->error('  ✗ Login failed: ' . ($loginResult['desc'] ?? 'Unknown'));
                }
            }
        }
    }
}