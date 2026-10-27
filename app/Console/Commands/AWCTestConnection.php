<?php
// app/Console/Commands/AWCTestConnection.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AWCTestConnection extends Command
{
    protected $signature = 'awc:test-connection';
    protected $description = 'Test AWC API connection with detailed output';
    
    public function handle()
    {
        $this->info('=== Testing AWC API Connection ===');
        $this->newLine();
        
        $apiUrl = config('awc.api_url', 'https://tttbo.apihub55.com/');
        $secretKey = config('awc.secret_key', '43asdfFFaxxx');
        $agentId = config('awc.agent_id', 'pg8688');
        
        $this->line("API URL: {$apiUrl}");
        $this->line("Agent ID: {$agentId}");
        $this->newLine();
        
        // Test 1: Simple connection test
        $this->info("Test 1: Testing basic connection...");
        try {
            $response = Http::timeout(10)->get($apiUrl);
            $this->line("   HTTP Status: " . $response->status());
            $this->line("   Response: " . substr($response->body(), 0, 200));
        } catch (\Exception $e) {
            $this->error("   Connection failed: " . $e->getMessage());
        }
        
        $this->newLine();
        
        // Test 2: Test createMember API
        $this->info("Test 2: Testing createMember API...");
        
        $testUserId = 'test_' . time();
        $message = json_encode([
            'action' => 'createMember',
            'agentId' => $agentId,
            'userId' => $testUserId,
            'currency' => 'MMK',
            'language' => 'en'
        ]);
        
        $cert = hash_hmac('sha256', $message, $secretKey);
        
        $this->line("   User ID: {$testUserId}");
        $this->line("   Cert: " . substr($cert, 0, 20) . "...");
        
        try {
            $response = Http::asForm()->timeout(30)->post($apiUrl, [
                'key' => $cert,
                'message' => $message,
            ]);
            
            $this->line("   HTTP Status: " . $response->status());
            $this->line("   Response Body: " . $response->body());
            
            $json = $response->json();
            if ($json) {
                $this->line("   Status Code: " . ($json['status'] ?? 'unknown'));
                $this->line("   Message: " . ($json['desc'] ?? 'No description'));
                
                if (isset($json['status']) && $json['status'] === '0000') {
                    $this->info("   ✓ API call successful!");
                } elseif (isset($json['status']) && $json['status'] === '1029') {
                    $this->error("   ✗ IP not whitelisted!");
                    $this->error("   Please whitelist IP: 82.180.152.194");
                } else {
                    $this->error("   ✗ API returned error: " . ($json['desc'] ?? 'Unknown'));
                }
            } else {
                $this->error("   ✗ Invalid JSON response");
            }
            
        } catch (\Exception $e) {
            $this->error("   Exception: " . $e->getMessage());
        }
        
        $this->newLine();
        
        // Test 3: Test with different cert generation method
        $this->info("Test 3: Testing with MD5 cert...");
        
        $md5Cert = md5($secretKey . $message);
        
        try {
            $response = Http::asForm()->timeout(30)->post($apiUrl, [
                'key' => $md5Cert,
                'message' => $message,
            ]);
            
            $this->line("   HTTP Status: " . $response->status());
            $this->line("   Response: " . substr($response->body(), 0, 200));
            
        } catch (\Exception $e) {
            $this->error("   Failed: " . $e->getMessage());
        }
    }
}