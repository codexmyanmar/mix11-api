<?php
// app/Traits/LogsApiCalls.php

namespace App\Traits;

use App\Models\ApiLog;
use Illuminate\Support\Facades\Log;

trait LogsApiCalls
{
    /**
     * Log API call before sending
     */
    protected function logApiRequest($apiName, $method, $url, $payload, $logId = null)
    {
        try {
            if (!$logId) {
                $log = ApiLog::create([
                    'api_name' => $apiName,
                    'method' => $method,
                    'url' => $url,
                    'request_payload' => $payload,
                    'status' => 'pending'
                ]);
                return $log->id;
            }
            return $logId;
        } catch (\Exception $e) {
            Log::error('Failed to log API request', [
                'api_name' => $apiName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Update API log after response
     */
    protected function logApiResponse($logId, $response, $status, $errorMessage = null)
    {
        if (!$logId) {
            return;
        }
        
        try {
            $log = ApiLog::find($logId);
            if ($log) {
                $updateData = [
                    'status' => $status, // 'success' or 'failed'
                    'updated_at' => now()
                ];
                
                if ($response !== null) {
                    $updateData['response'] = $response;
                }
                
                if ($errorMessage !== null) {
                    $updateData['error_message'] = substr($errorMessage, 0, 5000); // Limit error message length
                }
                
                $log->update($updateData);
            }
        } catch (\Exception $e) {
            Log::error('Failed to log API response', [
                'log_id' => $logId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Log API error
     */
    protected function logApiError($apiName, $method, $url, $payload, $error, $response = null)
    {
        try {
            ApiLog::create([
                'api_name' => $apiName,
                'method' => $method,
                'url' => $url,
                'request_payload' => $payload,
                'response' => $response,
                'status' => 'failed',
                'error_message' => substr(is_string($error) ? $error : $error->getMessage(), 0, 5000)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log API error', [
                'error' => $e->getMessage()
            ]);
        }
    }
}