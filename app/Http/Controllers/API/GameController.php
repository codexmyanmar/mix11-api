<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\KingKongHashService;
use App\Models\User;
use App\Models\KingkongTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    public function launchKingKongGame(Request $request)
    {
        Log::info("[launchKingKongGame] ========== START ==========");
        Log::info("[launchKingKongGame] Request data: " . json_encode($request->all()));
        
        try {
            Log::info("[launchKingKongGame] Validating request...");
            $validated = $request->validate([
                'game_code'  => 'required|string',
            ]);
            Log::info("[launchKingKongGame] Validation passed. Game code: " . $validated['game_code']);

            Log::info("[launchKingKongGame] Getting authenticated user...");
            $user = $request->user();
            if (!$user) {
                Log::warning("[launchKingKongGame] Unauthenticated user detected");
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user.'
                ], 401);
            }
            Log::info("[launchKingKongGame] User authenticated: ID={$user->id}, Name={$user->user_id}");

            Log::info("[launchKingKongGame] Loading configuration...");
            $forwardUrl = env('KINGKONG_FORWARD_URL_LOBBY');
            $appId      = env('KINGKONG_APP_ID');
            if (!$forwardUrl || !$appId) {
                Log::error("[launchKingKongGame] Configuration error - Forward URL: " . ($forwardUrl ?: 'missing') . ", App ID: " . ($appId ?: 'missing'));
                return response()->json([
                    'success' => false,
                    'message' => 'Game provider configuration error.'
                ], 500);
            }
            Log::info("[launchKingKongGame] Configuration loaded - Forward URL: {$forwardUrl}, App ID: {$appId}");

            Log::info("[launchKingKongGame] Checking/creating token...");
            $token = $user->kingkong_token ?? null;

            if (empty($token)) {
                Log::info("[launchKingKongGame] No existing token found, generating new token...");
                $token = urlencode(substr(Str::random(64), 0, 64));
                $user->kingkong_token = $token;
                $user->save();
                Log::info("[launchKingKongGame] New token generated and saved: {$token}");
            } else {
                Log::info("[launchKingKongGame] Using existing token: {$token}");
            }

            Log::info("[launchKingKongGame] Storing token in cache...");
            Cache::put(
                "kingkong_token_{$token}",
                $user->id,
                now()->addMinutes(5)
            );
            Log::info("[launchKingKongGame] Token cached with 5 minutes expiry");

            $gameCode = $validated['game_code'];
            $language = 'en';
            $mobile   = 'false';
            $redirectUrl = "https://zvz-commerce.com/api/kingkong";
            Log::info("[launchKingKongGame] Parameters - GameCode: {$gameCode}, Language: {$language}, Mobile: {$mobile}, RedirectUrl: {$redirectUrl}");

            $params = [
                'token'       => $token,
                'appId'       => $appId,
                'gameCode'    => $gameCode,
                'language'    => $language,
                'mobile'      => $mobile,
                'redirectUrl' => $redirectUrl,
            ];
            Log::info("[launchKingKongGame] Query parameters: " . json_encode($params));

            $launchUrl = rtrim($forwardUrl, '/') . '/playGame?' . http_build_query($params);
            Log::info("[launchKingKongGame] Generated launch URL: {$launchUrl}");

            Log::info("[launchKingKongGame] ========== SUCCESS ==========");
            return response()->json([
                'success' => true,
                'data' => [
                    'launch_url'   => $launchUrl,
                    'token_expiry' => '5 minutes'
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("[launchKingKongGame] Validation error: " . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("[launchKingKongGame] Exception caught: " . $e->getMessage());
            Log::error("[launchKingKongGame] Stack trace: " . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to launch game.'
            ], 500);
        }
    }

public function authenticate(Request $request, KingKongHashService $kingKongService)
{
    Log::info("[authenticate] ========== START ==========");
    Log::info("[authenticate] Request data: " . json_encode($request->all()));
    
    $appid = env('KINGKONG_APP_ID');
    $secretKey = env('KINGKONG_SECRET_KEY');
    $password = env('KINGKONG_PASSWORD', 'password'); // Add this to your .env file
    
    Log::info("[authenticate] App ID from env: {$appid}");
    
    $reqAppId  = $request->input('appid');
    $username  = $request->input('username');
    $ip        = $request->input('ip');
    $timestamp = $request->input('timestamp');
    $hash      = $request->input('hash');
    $reqPass   = $request->input('password');
    
    Log::info("[authenticate] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, IP: {$ip}, Timestamp: {$timestamp}");
    
    // Validate App ID
    if ($reqAppId !== $appid) {
        Log::warning("[authenticate] Invalid App ID");
        return response()->json([
            "Balance" => 0.0,
            "Message" => "Invalid AppID",
            "Status" => 2,
            "Token" => "null"
        ]);
    }
    
    // Validate password
    if ($reqPass !== $password) {
        Log::warning("[authenticate] Invalid password");
        return response()->json([
            "Balance" => 0.0,
            "Message" => "Invalid Username or Password",
            "Status" => 7,
            "Token" => "null"
        ]);
    }
    
    // Validate hash
    $payloadForHash = [
        'appid'     => $reqAppId,
        'ip'        => $ip,
        'password'  => $password,
        'timestamp' => $timestamp,
        'username'  => $username
    ];
    
    $generatedHash = $kingKongService->generateHash($payloadForHash);
    Log::info("[authenticate] Generated hash: {$generatedHash}, Received hash: {$hash}");
    
    if ($generatedHash !== $hash) {
        Log::warning("[authenticate] Hash mismatch");
        return response()->json([
            "Balance" => 0.0,
            "Message" => "Invalid hash",
            "Status" => 5,
            "Token" => "null"
        ]);
    }
    
    // Find or create user by username
    $user = \App\Models\User::where('user_id', $username)->first();
    
    if (!$user) {
        Log::warning("[authenticate] User not found for username: {$username}");
        return response()->json([
            "Balance" => 0.0,
            "Message" => "Invalid Username or Password",
            "Status" => 7,
            "Token" => "null"
        ]);
    }
    
    // Generate token for the session
    $token = $user->kingkong_token ?? null;
    if (empty($token)) {
        $token = urlencode(substr(Str::random(64), 0, 64));
        $user->kingkong_token = $token;
        $user->save();
    }
    
    // Store in cache
    Cache::put("kingkong_token_{$token}", $user->id, now()->addMinutes(5));
    
    Log::info("[authenticate] Authentication successful for user: {$username}");
    Log::info("[authenticate] ========== SUCCESS ==========");
    
    return response()->json([
        "Balance" => (float) $user->balance,
        "Message" => "Success",
        "Status" => 0,
        "Token" => $token
    ]);
}

  public function authtoken(Request $request, KingKongHashService $kingKongService)
{
    Log::info("[authtoken] ========== START ==========");
    Log::info("[authtoken] Request data: " . json_encode($request->all()));
    
    $appid = env('KINGKONG_APP_ID');
    $secretKey = env('KINGKONG_SECRET_KEY');
    Log::info("[authtoken] App ID from env: {$appid}");

    $token     = $request->input('token');
    $ip        = $request->input('ip');
    $timestamp = $request->input('timestamp');
    $hash      = $request->input('hash');
    $reqAppId  = $request->input('appid');
    
    Log::info("[authtoken] Parsed parameters - Token: " . ($token ?: 'missing') . ", IP: " . ($ip ?: 'missing') . ", Timestamp: {$timestamp}, Hash: " . ($hash ?: 'missing') . ", ReqAppId: {$reqAppId}");

    Log::info("[authtoken] Validating App ID...");
    if ($reqAppId !== $appid) {
        Log::warning("[authtoken] Invalid App ID - Received: {$reqAppId}, Expected: {$appid}");
        return response()->json([
            "Balance" => 0.0,
            "Message" => "Invalid AppID",
            "Status" => 2,
            "Username" => "null"
        ]);
    }
    Log::info("[authtoken] App ID validation passed");

    Log::info("[authtoken] Checking required parameters...");
    if (!$token || !$ip || !$timestamp || !$hash) {
        Log::warning("[authtoken] Missing parameters");
        return response()->json([
            "Balance" => 0.0,
            "Message" => "Missing parameters",
            "Status" => 1,
            "Username" => "null"
        ]);
    }
    Log::info("[authtoken] All required parameters present");

    $payloadForHash = [
        'appid'     => $reqAppId,
        'ip'        => $ip,
        'timestamp' => $timestamp,
        'token'     => $token
    ];
    Log::info("[authtoken] Generating hash for payload: " . json_encode($payloadForHash));

    $generatedHash = $kingKongService->generateHash($payloadForHash);
    Log::info("[authtoken] Generated hash: {$generatedHash}, Received hash: {$hash}");

    if ($generatedHash !== $hash) {
        Log::warning("[authtoken] Hash mismatch");
        return response()->json([
            "Balance" => 0.0,
            "Message" => "Invalid hash",
            "Status" => 5,
            "Username" => "null"
        ]);
    }
    Log::info("[authtoken] Hash validation passed");

    $currentTimestamp = now()->timestamp * 1000;
    $timeDiff = abs($currentTimestamp - $timestamp);
    Log::info("[authtoken] Checking timestamp expiry - Current: {$currentTimestamp}, Received: {$timestamp}, Difference: {$timeDiff}ms");
    
    if ($timeDiff > 30000) {
        Log::warning("[authtoken] Request expired - Time difference: {$timeDiff}ms");
        return response()->json([
            "Balance" => 0.0,
            "Message" => "Request expired",
            "Status" => 4,
            "Username" => "null"
        ]);
    }
    Log::info("[authtoken] Timestamp validation passed");

    Log::info("[authtoken] Looking up user by token: {$token}");
    $user = \App\Models\User::where('kingkong_token', $token)->first();

    if (!$user) {
        Log::warning("[authtoken] User not found for token: {$token}");
        return response()->json([
            "Balance" => 0.0,
            "Message" => "InvalidToken",
            "Status" => 3,
            "Username" => "null"
        ]);
    }
    Log::info("[authtoken] User found - ID: {$user->id}, user_id: {$user->user_id}, Balance: {$user->balance}");

    $balance = $user->balance; 
    $gameUsername = (string) $user->user_id;
    
    Log::info("[authtoken] Returning user data - Username: {$gameUsername}, Balance: {$balance}");
    Log::info("[authtoken] ========== SUCCESS ==========");
    
    return response()->json([
        "Balance" => (float) $balance,
        "Message" => "Success",
        "Status" => 0,
        "Username" => $gameUsername
    ]);
}
    
    public function getbalance(Request $request, KingKongHashService $kingKongService)
{
    Log::info("[getbalance] ========== START ==========");
    Log::info("[getbalance] Request data: " . json_encode($request->all()));
    
    $appid = env('KINGKONG_APP_ID');
    Log::info("[getbalance] App ID from env: {$appid}");

    $reqAppId  = $request->input('appid');
    $username  = $request->input('username');
    $token     = $request->input('token'); // Some implementations use token instead of username
    $timestamp = $request->input('timestamp');
    $hash      = $request->input('hash');
    
    Log::info("[getbalance] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, Token: {$token}, Timestamp: {$timestamp}");
    
    // Validate App ID
    if ($reqAppId !== $appid) {
        Log::warning("[getbalance] Invalid App ID");
        return response()->json(["Status" => 2, "Message" => "Invalid AppID", "Balance" => 0.0]);
    }
    
    // Find user by either username or token
    $user = null;
    if ($username) {
        Log::info("[getbalance] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
    } elseif ($token) {
        Log::info("[getbalance] Looking up user by token: {$token}");
        $user = \App\Models\User::where('kingkong_token', $token)->first();
    }
    
    if (!$user) {
        Log::warning("[getbalance] User not found");
        return response()->json(["Status" => 7, "Message" => "Invalid Username", "Balance" => 0.0]);
    }
    
    // If token doesn't match, update it
    if ($token && $user->kingkong_token !== $token) {
        $user->kingkong_token = $token;
        $user->save();
        Cache::put("kingkong_token_{$token}", $user->id, now()->addMinutes(5));
    }
    
    // Validate hash if username is provided
    if ($username) {
        $payloadForHash = [
            'appid'     => $reqAppId,
            'timestamp' => $timestamp,
            'username'  => $username
        ];
        
        $generatedHash = $kingKongService->generateHash($payloadForHash);
        Log::info("[getbalance] Generated hash: {$generatedHash}, Received hash: {$hash}");
        
        if ($generatedHash !== $hash) {
            Log::warning("[getbalance] Hash mismatch");
            return response()->json(["Status" => 5, "Message" => "Invalid hash", "Balance" => 0.0]);
        }
    }
    
    Log::info("[getbalance] User found - ID: {$user->id}, user_id: {$user->user_id}, Balance: {$user->balance}");
    
    $balance = (float) ($user->balance ?? 0.00);
    Log::info("[getbalance] Returning balance: {$balance}");
    Log::info("[getbalance] ========== SUCCESS ==========");
    
    return response()->json([
        "Balance" => $balance,
        "Message" => "Success",
        "Status"  => 0
    ]);
}

    public function bet(Request $request, KingKongHashService $kingKongService)
    {
        Log::info("[bet] ========== START ==========");
        Log::info("[bet] Request data: " . json_encode($request->all()));

        $reqAppId  = $request->input('appid');
        $username  = $request->input('username');
        $amount    = (float) $request->input('amount');
        $betId     = $request->input('id');
        $roundId   = $request->input('roundid');
        $gameCode  = $request->input('gamecode');
        $timestamp = $request->input('timestamp');
        $hash      = $request->input('hash');
        
        Log::info("[bet] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, Amount: {$amount}, BetId: {$betId}, RoundId: {$roundId}, GameCode: {$gameCode}, Timestamp: {$timestamp}");

        $payloadForHash = [
            'amount'    => $request->input('amount'),
            'appid'     => $reqAppId,
            'gamecode'  => $gameCode,
            'id'        => $betId,
            'roundid'   => $roundId,
            'timestamp' => $timestamp,
            'username'  => $username
        ];
        Log::info("[bet] Generating hash for payload: " . json_encode($payloadForHash));

        $generatedHash = $kingKongService->generateHash($payloadForHash);
        Log::info("[bet] Generated hash: {$generatedHash}, Received hash: {$hash}");

        if ($generatedHash !== $hash) {
            Log::error("[bet] Hash validation failed");
            return response()->json(["Status" => 5, "Message" => "Invalid hash"]);
        }
        Log::info("[bet] Hash validation passed");

        Log::info("[bet] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
        if (!$user) {
            Log::warning("[bet] User not found for user_id: {$username}");
            return response()->json(["Status" => 7, "Message" => "Invalid Username"]);
        }
        Log::info("[bet] User found - ID: {$user->id}, user_id: {$user->user_id}, Current balance: {$user->balance}");

        try {
            Log::info("[bet] Starting database transaction");
            DB::beginTransaction();

            Log::info("[bet] Checking for existing bet with ID: {$betId}");
            $existingBet = \App\Models\KingkongTransaction::where('bet_id', $betId)->first();
            if ($existingBet) {
                Log::info("[bet] Bet already exists - returning existing balance");
                DB::rollBack();
                return response()->json(["Balance" => (float)$user->balance, "Message" => "Success", "Status" => 0]);
            }
            Log::info("[bet] No existing bet found - proceeding");

            Log::info("[bet] Checking sufficient funds - Required: {$amount}, Available: {$user->balance}");
            if ($user->balance < $amount) {
                Log::warning("[bet] Insufficient funds - Required: {$amount}, Available: {$user->balance}");
                DB::rollBack();
                return response()->json(["Status" => 100, "Message" => "Insufficient fund"]);
            }
            Log::info("[bet] Sufficient funds available");

            Log::info("[bet] Deducting balance: {$amount}");
            $user->decrement('balance', $amount);
            Log::info("[bet] New balance after deduction: {$user->balance}");

            Log::info("[bet] Creating transaction record");
            \App\Models\KingkongTransaction::create([
                'user_id'    => $user->id,
                'bet_id'     => $betId,
                'round_id'   => $roundId,
                'game_code'  => $gameCode,
                'bet_amount' => $amount,
                'status'     => 'pending'
            ]);
            Log::info("[bet] Transaction record created");

            Log::info("[bet] Committing transaction");
            DB::commit();

            Log::info("[bet] ========== SUCCESS ==========");
            return response()->json([
                "Balance" => (float) $user->fresh()->balance,
                "Message" => "Success",
                "Status"  => 0
            ]);

        } catch (\Exception $e) {
            Log::error("[bet] Exception caught: " . $e->getMessage());
            Log::error("[bet] Stack trace: " . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(["Status" => 1000, "Message" => "Internal Server Error"]);
        }
    }

    public function settleBet(Request $request, KingKongHashService $kingKongService)
    {
        Log::info("[settleBet] ========== START ==========");
        Log::info("[settleBet] Request data: " . json_encode($request->all()));

        $reqAppId    = $request->input('appid');
        $username    = $request->input('username');
        $amount      = (float) $request->input('amount'); 
        $settleId    = $request->input('id');
        $roundId     = $request->input('roundid');
        $gameCode    = $request->input('gamecode');
        $timestamp   = $request->input('timestamp');
        $description = $request->input('description');
        $type        = $request->input('type');
        $hash        = $request->input('hash');
        
        Log::info("[settleBet] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, Amount: {$amount}, SettleId: {$settleId}, RoundId: {$roundId}, GameCode: {$gameCode}, Type: {$type}");

        $payloadForHash = [
            'amount'      => $request->input('amount'),
            'appid'       => $reqAppId,
            'description' => $description,
            'gamecode'    => $gameCode,
            'id'          => $settleId,
            'roundid'     => $roundId,
            'timestamp'   => $timestamp,
            'type'        => $type,
            'username'    => $username
        ];
        Log::info("[settleBet] Generating hash for payload: " . json_encode($payloadForHash));

        $generatedHash = $kingKongService->generateHash($payloadForHash);
        Log::info("[settleBet] Generated hash: {$generatedHash}, Received hash: {$hash}");

        if ($generatedHash !== $hash) {
            Log::error("[settleBet] Hash validation failed");
            return response()->json(["Status" => 5, "Message" => "Invalid hash"]);
        }
        Log::info("[settleBet] Hash validation passed");

        Log::info("[settleBet] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
        if (!$user) {
            Log::warning("[settleBet] User not found for user_id: {$username}");
            return response()->json(["Status" => 7, "Message" => "Invalid Username"]);
        }
        Log::info("[settleBet] User found - ID: {$user->id}, user_id: {$user->user_id}, Current balance: {$user->balance}");

        try {
            Log::info("[settleBet] Starting database transaction");
            DB::beginTransaction();

            Log::info("[settleBet] Checking for existing settle transaction with ID: {$settleId}");
            $existingSettle = \App\Models\KingkongTransaction::where('settle_id', $settleId)->first();
            if ($existingSettle) {
                Log::info("[settleBet] Settle transaction already exists - returning existing balance");
                DB::rollBack();
                return response()->json(["Balance" => (float)$user->balance, "Message" => "Success", "Status" => 0]);
            }
            Log::info("[settleBet] No existing settle transaction found");

            Log::info("[settleBet] Looking for pending transaction with round ID: {$roundId}");
            $transaction = \App\Models\KingkongTransaction::where('round_id', $roundId)
                                ->where('status', 'pending')->first();
            
            if ($transaction) {
                Log::info("[settleBet] Found pending transaction - ID: {$transaction->id}");
            } else {
                Log::info("[settleBet] No pending transaction found for round ID: {$roundId}");
            }

            if ($amount > 0) {
                Log::info("[settleBet] Adding win amount: {$amount} to user balance");
                $user->increment('balance', $amount);
                Log::info("[settleBet] New balance after increment: {$user->balance}");
            } else {
                Log::info("[settleBet] No positive amount to add to balance");
            }

            if ($transaction) {
                Log::info("[settleBet] Updating existing transaction with settle_id: {$settleId}, win_amount: {$amount}");
                $transaction->update(['settle_id' => $settleId, 'win_amount' => $amount, 'status' => 'settled']);
            } else {
                Log::info("[settleBet] Creating new transaction record (orphaned settlement)");
                \App\Models\KingkongTransaction::create([
                    'user_id' => $user->id, 'bet_id' => 'orphaned_' . uniqid(),
                    'settle_id' => $settleId, 'round_id' => $roundId,
                    'game_code' => $gameCode, 'win_amount' => $amount, 'status' => 'settled'
                ]);
            }

            Log::info("[settleBet] Committing transaction");
            DB::commit();

            Log::info("[settleBet] ========== SUCCESS ==========");
            return response()->json(["Balance" => (float) $user->fresh()->balance, "Message" => "Success", "Status" => 0]);

        } catch (\Exception $e) {
            Log::error("[settleBet] Exception caught: " . $e->getMessage());
            Log::error("[settleBet] Stack trace: " . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(["Status" => 1000, "Message" => "Error"]);
        }
    }

    public function cancelBet(Request $request, KingKongHashService $kingKongService)
    {
        Log::info("[cancelBet] ========== START ==========");
        Log::info("[cancelBet] Request data: " . json_encode($request->all()));
        
        $username = $request->input('username');
        $betId    = $request->input('betid');
        
        Log::info("[cancelBet] Parsed parameters - Username: {$username}, BetId: {$betId}");

        Log::info("[cancelBet] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
        if (!$user) {
            Log::warning("[cancelBet] User not found for user_id: {$username}");
            return response()->json(["Status" => 7, "Message" => "Invalid Username"]);
        }
        Log::info("[cancelBet] User found - ID: {$user->id}, user_id: {$user->user_id}, Current balance: {$user->balance}");

        try {
            Log::info("[cancelBet] Starting database transaction");
            DB::beginTransaction();

            Log::info("[cancelBet] Looking for transaction with bet ID: {$betId}");
            $transaction = \App\Models\KingkongTransaction::where('bet_id', $betId)->first();

            if ($transaction && $transaction->status !== 'cancelled') {
                Log::info("[cancelBet] Transaction found - Status: {$transaction->status}, Bet amount: {$transaction->bet_amount}");
                Log::info("[cancelBet] Refunding bet amount: {$transaction->bet_amount} to user");
                $user->increment('balance', $transaction->bet_amount);
                Log::info("[cancelBet] New balance after refund: {$user->balance}");
                
                Log::info("[cancelBet] Updating transaction status to cancelled");
                $transaction->update(['status' => 'cancelled']);
            } elseif ($transaction) {
                Log::info("[cancelBet] Transaction already cancelled");
            } else {
                Log::warning("[cancelBet] No transaction found for bet ID: {$betId}");
            }

            Log::info("[cancelBet] Committing transaction");
            DB::commit();

            Log::info("[cancelBet] ========== SUCCESS ==========");
            return response()->json(["Balance" => (float) $user->balance, "Message" => "Success", "Status" => 0]);

        } catch (\Exception $e) {
            Log::error("[cancelBet] Exception caught: " . $e->getMessage());
            Log::error("[cancelBet] Stack trace: " . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(["Status" => 1000, "Message" => "Error"]);
        }
    }
    
    public function bonusWin(Request $request, KingKongHashService $kingKongService)
    {
        Log::info("[bonusWin] ========== START ==========");
        Log::info("[bonusWin] Request data: " . json_encode($request->all()));

        $reqAppId    = $request->input('appid');
        $username    = $request->input('username');
        $amount      = (float) $request->input('amount');
        $txId        = $request->input('id');
        $roundId     = $request->input('roundid');
        $gameCode    = $request->input('gamecode');
        $timestamp   = $request->input('timestamp');
        $description = $request->input('description');
        $type        = $request->input('type');
        $hash        = $request->input('hash');
        
        Log::info("[bonusWin] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, Amount: {$amount}, TxId: {$txId}, RoundId: {$roundId}, GameCode: {$gameCode}");

        $payloadForHash = [
            'amount'      => $request->input('amount'),
            'appid'       => $reqAppId,
            'description' => $description,
            'gamecode'    => $gameCode,
            'id'          => $txId,
            'roundid'     => $roundId,
            'timestamp'   => $timestamp,
            'type'        => $type,
            'username'    => $username
        ];
        Log::info("[bonusWin] Generating hash for payload: " . json_encode($payloadForHash));

        $generatedHash = $kingKongService->generateHash($payloadForHash);
        Log::info("[bonusWin] Generated hash: {$generatedHash}, Received hash: {$hash}");

        if ($generatedHash !== $hash) {
            Log::error("[bonusWin] Hash validation failed");
            return response()->json(["Status" => 5, "Message" => "Invalid hash"]);
        }
        Log::info("[bonusWin] Hash validation passed");

        Log::info("[bonusWin] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
        if (!$user) {
            Log::warning("[bonusWin] User not found for user_id: {$username}");
            return response()->json(["Status" => 7, "Message" => "Invalid Username"]);
        }
        Log::info("[bonusWin] User found - ID: {$user->id}, user_id: {$user->user_id}, Current balance: {$user->balance}");

        try {
            Log::info("[bonusWin] Starting database transaction");
            DB::beginTransaction();

            Log::info("[bonusWin] Checking for existing transaction with settle_id: {$txId}");
            $existingTx = \App\Models\KingkongTransaction::where('settle_id', $txId)->first();
            if ($existingTx) {
                Log::info("[bonusWin] Transaction already exists - returning existing balance");
                DB::rollBack();
                return response()->json(["Balance" => (float)$user->balance, "Message" => "Success", "Status" => 0]);
            }
            Log::info("[bonusWin] No existing transaction found");

            if ($amount > 0) {
                Log::info("[bonusWin] Adding bonus amount: {$amount} to user balance");
                $user->increment('balance', $amount);
                Log::info("[bonusWin] New balance after increment: {$user->balance}");
            }

            Log::info("[bonusWin] Creating bonus transaction record");
            \App\Models\KingkongTransaction::create([
                'user_id'    => $user->id,
                'bet_id'     => 'bonus_' . uniqid(),
                'settle_id'  => $txId,
                'round_id'   => $roundId,
                'game_code'  => $gameCode,
                'win_amount' => $amount,
                'status'     => 'bonus_win'
            ]);
            Log::info("[bonusWin] Transaction record created");

            Log::info("[bonusWin] Committing transaction");
            DB::commit();

            Log::info("[bonusWin] ========== SUCCESS ==========");
            return response()->json(["Balance" => (float) $user->fresh()->balance, "Message" => "Success", "Status" => 0]);

        } catch (\Exception $e) {
            Log::error("[bonusWin] Exception caught: " . $e->getMessage());
            Log::error("[bonusWin] Stack trace: " . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(["Status" => 1000, "Message" => "Error"]);
        }
    }

    public function jackpotWin(Request $request, KingKongHashService $kingKongService)
    {
        Log::info("[jackpotWin] ========== START ==========");
        Log::info("[jackpotWin] Request data: " . json_encode($request->all()));

        $reqAppId    = $request->input('appid');
        $username    = $request->input('username');
        $amount      = (float) $request->input('amount');
        $txId        = $request->input('id');
        $roundId     = $request->input('roundid');
        $gameCode    = $request->input('gamecode');
        $timestamp   = $request->input('timestamp');
        $description = $request->input('description');
        $type        = $request->input('type');
        $hash        = $request->input('hash');
        
        Log::info("[jackpotWin] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, Amount: {$amount}, TxId: {$txId}, RoundId: {$roundId}");

        $payloadForHash = [
            'amount'      => $request->input('amount'),
            'appid'       => $reqAppId,
            'description' => $description,
            'gamecode'    => $gameCode,
            'id'          => $txId,
            'roundid'     => $roundId,
            'timestamp'   => $timestamp,
            'type'        => $type,
            'username'    => $username
        ];
        Log::info("[jackpotWin] Generating hash for payload: " . json_encode($payloadForHash));

        $generatedHash = $kingKongService->generateHash($payloadForHash);
        Log::info("[jackpotWin] Generated hash: {$generatedHash}, Received hash: {$hash}");

        if ($generatedHash !== $hash) {
            Log::error("[jackpotWin] Hash validation failed");
            return response()->json(["Status" => 5, "Message" => "Invalid hash"]);
        }
        Log::info("[jackpotWin] Hash validation passed");

        Log::info("[jackpotWin] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
        if (!$user) {
            Log::warning("[jackpotWin] User not found for user_id: {$username}");
            return response()->json(["Status" => 7, "Message" => "Invalid Username"]);
        }
        Log::info("[jackpotWin] User found - ID: {$user->id}, user_id: {$user->user_id}, Current balance: {$user->balance}");

        try {
            Log::info("[jackpotWin] Starting database transaction");
            DB::beginTransaction();

            Log::info("[jackpotWin] Checking for existing transaction with settle_id: {$txId}");
            $existingTx = \App\Models\KingkongTransaction::where('settle_id', $txId)->first();
            if ($existingTx) {
                Log::info("[jackpotWin] Transaction already exists - returning existing balance");
                DB::rollBack();
                return response()->json(["Balance" => (float)$user->balance, "Message" => "Success", "Status" => 0]);
            }
            Log::info("[jackpotWin] No existing transaction found");

            if ($amount > 0) {
                Log::info("[jackpotWin] Adding jackpot amount: {$amount} to user balance");
                $user->increment('balance', $amount);
                Log::info("[jackpotWin] New balance after increment: {$user->balance}");
            }

            Log::info("[jackpotWin] Creating jackpot transaction record");
            \App\Models\KingkongTransaction::create([
                'user_id'    => $user->id,
                'bet_id'     => 'jackpot_' . uniqid(),
                'settle_id'  => $txId,
                'round_id'   => $roundId,
                'game_code'  => $gameCode,
                'win_amount' => $amount,
                'status'     => 'jackpot_win'
            ]);
            Log::info("[jackpotWin] Transaction record created");

            Log::info("[jackpotWin] Committing transaction");
            DB::commit();

            Log::info("[jackpotWin] ========== SUCCESS ==========");
            return response()->json(["Balance" => (float) $user->fresh()->balance, "Message" => "Success", "Status" => 0]);
        } catch (\Exception $e) {
            Log::error("[jackpotWin] Exception caught: " . $e->getMessage());
            Log::error("[jackpotWin] Stack trace: " . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(["Status" => 1000, "Message" => "Error"]);
        }
    }

 public function fishingTransaction(Request $request, KingKongHashService $kingKongService)
{
    Log::info("[fishingTransaction] ========== START ==========");
    Log::info("[fishingTransaction] Request data: " . json_encode($request->all()));

    $reqAppId    = $request->input('appid');
    $username    = $request->input('username');
    $amount      = (float) $request->input('amount');
    $result      = (float) $request->input('result');
    $txId        = $request->input('id');
    $roundId     = $request->input('roundid');
    $gameCode    = $request->input('gamecode');
    $timestamp   = $request->input('timestamp');
    $description = $request->input('description');
    $type        = $request->input('type');
    $startBal    = $request->input('startbalance');
    $endBal      = $request->input('endbalance');
    $hash        = $request->input('hash');
    
    Log::info("[fishingTransaction] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, Amount: {$amount}, Result: {$result}, TxId: {$txId}");
    
    // Validate App ID
    $appid = env('KINGKONG_APP_ID');
    if ($reqAppId !== $appid) {
        Log::warning("[fishingTransaction] Invalid App ID");
        return response()->json(["Status" => 2, "Message" => "Invalid AppID", "Balance" => 0.0]);
    }
    
    // IMPORTANT: Format numbers with 2 decimal places for hash calculation
    $formattedAmount = number_format($amount, 2, '.', '');
    $formattedResult = number_format($result, 2, '.', '');
    $formattedStartBal = number_format($startBal, 2, '.', '');
    $formattedEndBal = number_format($endBal, 2, '.', '');
    
    $payloadForHash = [
        'amount'       => $formattedAmount,
        'appid'        => $reqAppId,
        'description'  => $description,
        'endbalance'   => $formattedEndBal,
        'gamecode'     => $gameCode,
        'id'           => $txId,
        'result'       => $formattedResult,
        'roundid'      => $roundId,
        'startbalance' => $formattedStartBal,
        'timestamp'    => $timestamp,
        'type'         => $type,
        'username'     => $username
    ];
    
    Log::info("[fishingTransaction] Generating hash for payload: " . json_encode($payloadForHash));
    
    // Sort the payload by keys (most providers require sorted order)
    ksort($payloadForHash);
    Log::info("[fishingTransaction] Sorted payload: " . json_encode($payloadForHash));

    $generatedHash = $kingKongService->generateHash($payloadForHash);
    Log::info("[fishingTransaction] Generated hash: {$generatedHash}, Received hash: {$hash}");

    if ($generatedHash !== $hash) {
        Log::error("[fishingTransaction] Hash validation failed");
        return response()->json(["Status" => 5, "Message" => "Invalid hash", "Balance" => 0.0]);
    }
    Log::info("[fishingTransaction] Hash validation passed");

    Log::info("[fishingTransaction] Looking up user by user_id: {$username}");
    $user = \App\Models\User::where('user_id', $username)->first();
    if (!$user) {
        Log::warning("[fishingTransaction] User not found for user_id: {$username}");
        return response()->json(["Status" => 7, "Message" => "Invalid Username", "Balance" => 0.0]);
    }
    Log::info("[fishingTransaction] User found - ID: {$user->id}, user_id: {$user->user_id}, Balance: {$user->balance}");

    try {
        Log::info("[fishingTransaction] Creating/updating fishing audit record");
        
        // Check if transaction already exists
        $existingTx = \App\Models\KingkongTransaction::where('bet_id', $txId)->first();
        if ($existingTx) {
            Log::info("[fishingTransaction] Transaction already exists");
            return response()->json([
                "Balance" => (float) $user->balance,
                "Message" => "Success", 
                "Status" => 0
            ]);
        }
        
        \App\Models\KingkongTransaction::create([
            'user_id'    => $user->id,
            'bet_id'     => $txId,
            'round_id'   => $roundId,
            'game_code'  => $gameCode,
            'bet_amount' => $amount,
            'win_amount' => $result,
            'status'     => 'fishing_audit'
        ]);
        Log::info("[fishingTransaction] Fishing audit record created");

        Log::info("[fishingTransaction] ========== SUCCESS ==========");
        
        // Return the start balance as expected by the test panel
        return response()->json([
            "Balance" => (float) $startBal,
            "Message" => "Success", 
            "Status" => 0
        ]);
        
    } catch (\Exception $e) {
        Log::error("[fishingTransaction] Exception caught: " . $e->getMessage());
        Log::error("[fishingTransaction] Stack trace: " . $e->getTraceAsString());
        return response()->json(["Status" => 1000, "Message" => "Error", "Balance" => 0.0]);
    }
}

    public function fishingWithdraw(Request $request, KingKongHashService $kingKongService)
    {
        Log::info("[fishingWithdraw] ========== START ==========");
        Log::info("[fishingWithdraw] Request data: " . json_encode($request->all()));

        $reqAppId  = $request->input('appid');
        $username  = $request->input('username');
        $amount    = (float) $request->input('amount');
        $txId      = $request->input('id');
        $timestamp = $request->input('timestamp');
        $hash      = $request->input('hash');
        
        Log::info("[fishingWithdraw] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, Amount: {$amount}, TxId: {$txId}");

        $payloadForHash = [
            'amount'    => $request->input('amount'),
            'appid'     => $reqAppId,
            'id'        => $txId,
            'timestamp' => $timestamp,
            'username'  => $username
        ];
        Log::info("[fishingWithdraw] Generating hash for payload: " . json_encode($payloadForHash));

        $generatedHash = $kingKongService->generateHash($payloadForHash);
        Log::info("[fishingWithdraw] Generated hash: {$generatedHash}, Received hash: {$hash}");

        if ($generatedHash !== $hash) {
            Log::error("[fishingWithdraw] Hash validation failed");
            return response()->json(["Status" => 5, "Message" => "Invalid hash"]);
        }
        Log::info("[fishingWithdraw] Hash validation passed");

        Log::info("[fishingWithdraw] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
        if (!$user) {
            Log::warning("[fishingWithdraw] User not found for user_id: {$username}");
            return response()->json(["Status" => 7, "Message" => "Invalid Username"]);
        }
        Log::info("[fishingWithdraw] User found - ID: {$user->id}, user_id: {$user->user_id}, Current balance: {$user->balance}");

        try {
            Log::info("[fishingWithdraw] Starting database transaction");
            DB::beginTransaction();

            Log::info("[fishingWithdraw] Checking for existing transaction with bet_id: {$txId}");
            $existingTx = \App\Models\KingkongTransaction::where('bet_id', $txId)->first();
            if ($existingTx) {
                Log::info("[fishingWithdraw] Transaction already exists - returning existing balance");
                DB::rollBack();
                return response()->json(["Balance" => (float)$user->balance, "Message" => "Success", "Status" => 0]);
            }
            Log::info("[fishingWithdraw] No existing transaction found");

            Log::info("[fishingWithdraw] Checking sufficient funds - Required: {$amount}, Available: {$user->balance}");
            if ($user->balance < $amount) {
                Log::warning("[fishingWithdraw] Insufficient funds");
                DB::rollBack();
                return response()->json(["Status" => 100, "Message" => "Insufficient fund"]);
            }
            Log::info("[fishingWithdraw] Sufficient funds available");

            Log::info("[fishingWithdraw] Deducting amount: {$amount} from user balance");
            $user->decrement('balance', $amount);
            Log::info("[fishingWithdraw] New balance after deduction: {$user->balance}");

            Log::info("[fishingWithdraw] Creating fishing withdraw transaction record");
            \App\Models\KingkongTransaction::create([
                'user_id'    => $user->id,
                'bet_id'     => $txId,
                'round_id'   => 'fishing_wd',
                'game_code'  => 'fishing',
                'bet_amount' => $amount,
                'status'     => 'fishing_withdraw'
            ]);
            Log::info("[fishingWithdraw] Transaction record created");

            Log::info("[fishingWithdraw] Committing transaction");
            DB::commit();
            
            Log::info("[fishingWithdraw] ========== SUCCESS ==========");
            return response()->json(["Balance" => (float) $user->fresh()->balance, "Message" => "Success", "Status" => 0]);
        } catch (\Exception $e) {
            Log::error("[fishingWithdraw] Exception caught: " . $e->getMessage());
            Log::error("[fishingWithdraw] Stack trace: " . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(["Status" => 1000, "Message" => "Error"]);
        }
    }

    public function fishingDeposit(Request $request, KingKongHashService $kingKongService)
    {
        Log::info("[fishingDeposit] ========== START ==========");
        Log::info("[fishingDeposit] Request data: " . json_encode($request->all()));

        $reqAppId  = $request->input('appid');
        $username  = $request->input('username');
        $amount    = (float) $request->input('amount');
        $txId      = $request->input('id');
        $timestamp = $request->input('timestamp');
        $hash      = $request->input('hash');
        
        Log::info("[fishingDeposit] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, Amount: {$amount}, TxId: {$txId}");

        $payloadForHash = [
            'amount'    => $request->input('amount'),
            'appid'     => $reqAppId,
            'id'        => $txId,
            'timestamp' => $timestamp,
            'username'  => $username
        ];
        Log::info("[fishingDeposit] Generating hash for payload: " . json_encode($payloadForHash));

        $generatedHash = $kingKongService->generateHash($payloadForHash);
        Log::info("[fishingDeposit] Generated hash: {$generatedHash}, Received hash: {$hash}");

        if ($generatedHash !== $hash) {
            Log::error("[fishingDeposit] Hash validation failed");
            return response()->json(["Status" => 5, "Message" => "Invalid hash"]);
        }
        Log::info("[fishingDeposit] Hash validation passed");

        Log::info("[fishingDeposit] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
        if (!$user) {
            Log::warning("[fishingDeposit] User not found for user_id: {$username}");
            return response()->json(["Status" => 7, "Message" => "Invalid Username"]);
        }
        Log::info("[fishingDeposit] User found - ID: {$user->id}, user_id: {$user->user_id}, Current balance: {$user->balance}");

        try {
            Log::info("[fishingDeposit] Starting database transaction");
            DB::beginTransaction();

            Log::info("[fishingDeposit] Checking for existing transaction with settle_id: {$txId}");
            $existingTx = \App\Models\KingkongTransaction::where('settle_id', $txId)->first();
            if ($existingTx) {
                Log::info("[fishingDeposit] Transaction already exists - returning existing balance");
                DB::rollBack();
                return response()->json(["Balance" => (float)$user->balance, "Message" => "Success", "Status" => 0]);
            }
            Log::info("[fishingDeposit] No existing transaction found");

            Log::info("[fishingDeposit] Adding deposit amount: {$amount} to user balance");
            $user->increment('balance', $amount);
            Log::info("[fishingDeposit] New balance after increment: {$user->balance}");

            Log::info("[fishingDeposit] Creating fishing deposit transaction record");
            \App\Models\KingkongTransaction::create([
                'user_id'    => $user->id,
                'bet_id'     => 'fishing_dep_' . uniqid(),
                'settle_id'  => $txId,
                'round_id'   => 'fishing_dep',
                'game_code'  => 'fishing',
                'win_amount' => $amount,
                'status'     => 'fishing_deposit'
            ]);
            Log::info("[fishingDeposit] Transaction record created");

            Log::info("[fishingDeposit] Committing transaction");
            DB::commit();
            
            Log::info("[fishingDeposit] ========== SUCCESS ==========");
            return response()->json(["Balance" => (float) $user->fresh()->balance, "Message" => "Success", "Status" => 0]);
        } catch (\Exception $e) {
            Log::error("[fishingDeposit] Exception caught: " . $e->getMessage());
            Log::error("[fishingDeposit] Stack trace: " . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(["Status" => 1000, "Message" => "Error"]);
        }
    }

    public function joinTournament(Request $request, KingKongHashService $kingKongService)
    {
        Log::info("[joinTournament] ========== START ==========");
        Log::info("[joinTournament] Request data: " . json_encode($request->all()));

        $reqAppId     = $request->input('appid');
        $username     = $request->input('username');
        $amount       = (float) $request->input('amount');
        $txId         = $request->input('id');
        $tournamentId = $request->input('tournamentid');
        $timestamp    = $request->input('timestamp');
        $hash         = $request->input('hash');
        
        Log::info("[joinTournament] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, Amount: {$amount}, TxId: {$txId}, TournamentId: {$tournamentId}");

        $payloadForHash = [
            'amount'       => $request->input('amount'),
            'appid'        => $reqAppId,
            'id'           => $txId,
            'timestamp'    => $timestamp,
            'tournamentid' => $tournamentId,
            'username'     => $username
        ];
        Log::info("[joinTournament] Generating hash for payload: " . json_encode($payloadForHash));

        $generatedHash = $kingKongService->generateHash($payloadForHash);
        Log::info("[joinTournament] Generated hash: {$generatedHash}, Received hash: {$hash}");

        if ($generatedHash !== $hash) {
            Log::error("[joinTournament] Hash validation failed");
            return response()->json(["Status" => 5, "Message" => "Invalid hash"]);
        }
        Log::info("[joinTournament] Hash validation passed");

        Log::info("[joinTournament] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
        if (!$user) {
            Log::warning("[joinTournament] User not found for user_id: {$username}");
            return response()->json(["Status" => 7, "Message" => "Invalid Username"]);
        }
        Log::info("[joinTournament] User found - ID: {$user->id}, user_id: {$user->user_id}, Current balance: {$user->balance}");

        try {
            Log::info("[joinTournament] Starting database transaction");
            DB::beginTransaction();

            Log::info("[joinTournament] Checking for existing transaction with bet_id: {$txId}");
            $existingTx = \App\Models\KingkongTransaction::where('bet_id', $txId)->first();
            if ($existingTx) {
                Log::info("[joinTournament] Transaction already exists - returning existing balance");
                DB::rollBack();
                return response()->json(["Balance" => (float)$user->balance, "Message" => "Success", "Status" => 0]);
            }
            Log::info("[joinTournament] No existing transaction found");

            Log::info("[joinTournament] Checking sufficient funds - Required: {$amount}, Available: {$user->balance}");
            if ($user->balance < $amount) {
                Log::warning("[joinTournament] Insufficient funds");
                DB::rollBack();
                return response()->json(["Status" => 100, "Message" => "Insufficient fund"]);
            }
            Log::info("[joinTournament] Sufficient funds available");

            Log::info("[joinTournament] Deducting tournament fee: {$amount} from user balance");
            $user->decrement('balance', $amount);
            Log::info("[joinTournament] New balance after deduction: {$user->balance}");

            Log::info("[joinTournament] Creating tournament join transaction record");
            \App\Models\KingkongTransaction::create([
                'user_id'    => $user->id,
                'bet_id'     => $txId,
                'round_id'   => $tournamentId,
                'game_code'  => 'tournament',
                'bet_amount' => $amount,
                'status'     => 'tournament_join'
            ]);
            Log::info("[joinTournament] Transaction record created");

            Log::info("[joinTournament] Committing transaction");
            DB::commit();
            
            Log::info("[joinTournament] ========== SUCCESS ==========");
            return response()->json(["Balance" => (float) $user->fresh()->balance, "Message" => "Success", "Status" => 0]);
        } catch (\Exception $e) {
            Log::error("[joinTournament] Exception caught: " . $e->getMessage());
            Log::error("[joinTournament] Stack trace: " . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(["Status" => 1000, "Message" => "Error"]);
        }
    }

    public function cancelJoinTournament(Request $request, KingKongHashService $kingKongService)
    {
        Log::info("[cancelJoinTournament] ========== START ==========");
        Log::info("[cancelJoinTournament] Request data: " . json_encode($request->all()));

        $reqAppId     = $request->input('appid');
        $username     = $request->input('username');
        $txId         = $request->input('id');
        $joinTxId     = $request->input('jointournamentid');
        $timestamp    = $request->input('timestamp');
        $hash         = $request->input('hash');
        
        Log::info("[cancelJoinTournament] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, TxId: {$txId}, JoinTxId: {$joinTxId}");

        $payloadForHash = [
            'appid'            => $reqAppId,
            'id'               => $txId,
            'jointournamentid' => $joinTxId,
            'timestamp'        => $timestamp,
            'username'         => $username
        ];
        Log::info("[cancelJoinTournament] Generating hash for payload: " . json_encode($payloadForHash));

        $generatedHash = $kingKongService->generateHash($payloadForHash);
        Log::info("[cancelJoinTournament] Generated hash: {$generatedHash}, Received hash: {$hash}");

        if ($generatedHash !== $hash) {
            Log::error("[cancelJoinTournament] Hash validation failed");
            return response()->json(["Status" => 5, "Message" => "Invalid hash"]);
        }
        Log::info("[cancelJoinTournament] Hash validation passed");

        Log::info("[cancelJoinTournament] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
        if (!$user) {
            Log::warning("[cancelJoinTournament] User not found for user_id: {$username}");
            return response()->json(["Status" => 7, "Message" => "Invalid Username"]);
        }
        Log::info("[cancelJoinTournament] User found - ID: {$user->id}, user_id: {$user->user_id}, Current balance: {$user->balance}");

        try {
            Log::info("[cancelJoinTournament] Starting database transaction");
            DB::beginTransaction();

            Log::info("[cancelJoinTournament] Looking for transaction with bet_id: {$joinTxId}");
            $transaction = \App\Models\KingkongTransaction::where('bet_id', $joinTxId)->first();

            if ($transaction && $transaction->status !== 'cancelled') {
                Log::info("[cancelJoinTournament] Transaction found - Status: {$transaction->status}, Bet amount: {$transaction->bet_amount}");
                Log::info("[cancelJoinTournament] Refunding tournament fee: {$transaction->bet_amount}");
                $user->increment('balance', $transaction->bet_amount);
                Log::info("[cancelJoinTournament] New balance after refund: {$user->balance}");
                
                Log::info("[cancelJoinTournament] Updating transaction status to cancelled");
                $transaction->update(['status' => 'cancelled']);
            } elseif ($transaction) {
                Log::info("[cancelJoinTournament] Transaction already cancelled");
            } else {
                Log::warning("[cancelJoinTournament] No transaction found for bet ID: {$joinTxId}");
            }

            Log::info("[cancelJoinTournament] Committing transaction");
            DB::commit();
            
            Log::info("[cancelJoinTournament] ========== SUCCESS ==========");
            return response()->json(["Balance" => (float) $user->fresh()->balance, "Message" => "Success", "Status" => 0]);
        } catch (\Exception $e) {
            Log::error("[cancelJoinTournament] Exception caught: " . $e->getMessage());
            Log::error("[cancelJoinTournament] Stack trace: " . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(["Status" => 1000, "Message" => "Error"]);
        }
    }

    public function winTournament(Request $request, KingKongHashService $kingKongService)
    {
        Log::info("[winTournament] ========== START ==========");
        Log::info("[winTournament] Request data: " . json_encode($request->all()));

        $reqAppId     = $request->input('appid');
        $username     = $request->input('username');
        $amount       = (float) $request->input('amount');
        $txId         = $request->input('id');
        $tournamentId = $request->input('tournamentid');
        $timestamp    = $request->input('timestamp');
        $hash         = $request->input('hash');
        
        Log::info("[winTournament] Parsed parameters - AppId: {$reqAppId}, Username: {$username}, Amount: {$amount}, TxId: {$txId}, TournamentId: {$tournamentId}");

        $payloadForHash = [
            'amount'       => $request->input('amount'),
            'appid'        => $reqAppId,
            'id'           => $txId,
            'timestamp'    => $timestamp,
            'tournamentid' => $tournamentId,
            'username'     => $username
        ];
        Log::info("[winTournament] Generating hash for payload: " . json_encode($payloadForHash));

        $generatedHash = $kingKongService->generateHash($payloadForHash);
        Log::info("[winTournament] Generated hash: {$generatedHash}, Received hash: {$hash}");

        if ($generatedHash !== $hash) {
            Log::error("[winTournament] Hash validation failed");
            return response()->json(["Status" => 5, "Message" => "Invalid hash"]);
        }
        Log::info("[winTournament] Hash validation passed");

        Log::info("[winTournament] Looking up user by user_id: {$username}");
        $user = \App\Models\User::where('user_id', $username)->first();
        if (!$user) {
            Log::warning("[winTournament] User not found for user_id: {$username}");
            return response()->json(["Status" => 7, "Message" => "Invalid Username"]);
        }
        Log::info("[winTournament] User found - ID: {$user->id}, user_id: {$user->user_id}, Current balance: {$user->balance}");

        try {
            Log::info("[winTournament] Starting database transaction");
            DB::beginTransaction();

            Log::info("[winTournament] Checking for existing transaction with settle_id: {$txId}");
            $existingTx = \App\Models\KingkongTransaction::where('settle_id', $txId)->first();
            if ($existingTx) {
                Log::info("[winTournament] Transaction already exists - returning existing balance");
                DB::rollBack();
                return response()->json(["Balance" => (float)$user->balance, "Message" => "Success", "Status" => 0]);
            }
            Log::info("[winTournament] No existing transaction found");

            if ($amount > 0) {
                Log::info("[winTournament] Adding tournament prize: {$amount} to user balance");
                $user->increment('balance', $amount);
                Log::info("[winTournament] New balance after increment: {$user->balance}");
            }

            Log::info("[winTournament] Creating tournament win transaction record");
            \App\Models\KingkongTransaction::create([
                'user_id'    => $user->id,
                'bet_id'     => 'tourn_win_' . uniqid(),
                'settle_id'  => $txId,
                'round_id'   => $tournamentId,
                'game_code'  => 'tournament',
                'win_amount' => $amount,
                'status'     => 'tournament_win'
            ]);
            Log::info("[winTournament] Transaction record created");

            Log::info("[winTournament] Committing transaction");
            DB::commit();
            
            Log::info("[winTournament] ========== SUCCESS ==========");
            return response()->json(["Balance" => (float) $user->fresh()->balance, "Message" => "Success", "Status" => 0]);
        } catch (\Exception $e) {
            Log::error("[winTournament] Exception caught: " . $e->getMessage());
            Log::error("[winTournament] Stack trace: " . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(["Status" => 1000, "Message" => "Error"]);
        }
    }
}