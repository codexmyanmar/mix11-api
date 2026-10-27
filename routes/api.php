<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\TestController;
use App\Http\Controllers\API\HomePageController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DCController;
use App\Http\Controllers\KingKongDomainController;
use App\Http\Controllers\API\GameController;
use App\Http\Controllers\API\JiliGameController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\API\PPPGameController;
use App\Http\Controllers\API\AWCGameController;
use App\Http\Controllers\API\BoleGameController;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\API\BuffaloController;
use App\Http\Controllers\API\Rich88GameController;
use App\Http\Controllers\API\AWCCallbackController;
use App\Http\Controllers\BoleWebhookController;
use Illuminate\Support\Facades\Storage;
use App\Models\Game;
use App\Http\Controllers\API\Live22CallbackController;
use App\Http\Controllers\API\Live22GameController;
use App\Http\Controllers\API\CQ9GameController;
use App\Http\Controllers\JokerGameController;
use App\Http\Controllers\FootballController;
use App\Http\Controllers\FootballAPIController;
use App\Http\Controllers\BettingController;
use App\Http\Controllers\API\Buffalo789Controller;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/* No Need */
Route::get("/testing",[TestController::class,"testing"]);
Route::get("/login",[TestController::class,"testing"])->name("login");

/* Home API */
Route::get("/banner/{user_id?}", [HomePageController::class, "banner"]);
Route::get("/announcement/{user_id?}", [HomePageController::class, "announcement"]);
Route::get("/promotion/{user_id?}",[HomePageController::class,"promotion"]);
Route::get("/category",[HomePageController::class,"category"]);
Route::get("/games",[HomePageController::class,"games"]);
Route::get("/providers", [HomePageController::class, "providers"]); // New endpoint
Route::get("/providersByCategory", [HomePageController::class, "providersByCategory"]); 
Route::get("/games-by-category", [HomePageController::class, "gamesByCategory"]); // New en
Route::get("/games-by-provider", [HomePageController::class, "gamesByProvider"]); // New en
Route::get("/games-by-casino", [HomePageController::class, "gamesByCasino"]); // New en
Route::get("/asia_royal_home",[HomePageController::class,"asia_royal_home"]);
/* Login / Register  */
Route::post("/login",[AuthController::class,"login"])->name("login");
Route::post("/register",[AuthController::class,"register"]);

Route::middleware('auth:sanctum')->group(function () {
    /* Balance */
    Route::get("/balance",[AuthController::class, 'balance']);
    Route::get("/user_id",[AuthController::class, 'user_id']);
    /* Logout / Profile / Setting */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post("/password-change",[AuthController::class,"passwordChange"]);
    Route::get("/profile",[AuthController::class,"profile"]);
    Route::get("/contact/info",[AuthController::class,"contactInfo"]);

    /* Debit / Credit / Log */
    Route::get("/payment_method_info/{type}",[DCController::class,"info"]);
    Route::get("/dc/log",[DCController::class,"dcLog"]);
    Route::post("/deposit",[DCController::class,"depositStore"]);
    Route::post("/withdraw",[DCController::class,"withdrawStore"]);


    /* Play */
    // Route::post('/game/launch/kingkong', [GameController::class, 'launchKingKongGame']);
    
    // /* jili */
    // Route::post("/game/lauch/jili",[JiliGameController::class, 'launchJiliGame']);
    
    // /* PP */
    // Route::post("/game/launch/pp", [PPPGameController::class, 'launchGame']);
    
    // /* AB */
    // Route::post("/game/launch/ab", [BuffaloController::class, 'launchGame']);
    // Route::post("/buffalo/refresh-balance", [BuffaloController::class, 'refreshBalance']);
    
    // /* Bufalo789 */
    // Route::post("/game/launch/buffalo789", [Buffalo789Controller::class, 'launch']);
    

    // /* AWC Game Integration */
    // Route::prefix('awc')->group(function () {
    //     // Member Management
    //     Route::post('/create-member', [AWCGameController::class, 'createMember']);
    //     Route::get('/player-info', [AWCGameController::class, 'getPlayerInfo']);
        
    //     // Game Launch
    //     Route::post('/login', [AWCGameController::class, 'login']);
    //     Route::post('/launch-lobby', [AWCGameController::class, 'launchLobby']);
    //     Route::post('/launch-game', [AWCGameController::class, 'launchGame']);
    //     Route::post('/launch-by-code', [AWCGameController::class, 'launchGameByCode']);
        
        
    //     // Game Lists
    //     Route::get('/games', [AWCGameController::class, 'getGames']);
    // });

    
    // // RICH88
    // Route::post('/rich88/launch', [Rich88GameController::class, 'launchGame']);
    // Route::get('/rich88/balance', [Rich88GameController::class, 'getBalance']);
    // Route::post('/rich88/deposit', [Rich88GameController::class, 'deposit']);
    // Route::post('/rich88/withdraw', [Rich88GameController::class, 'withdraw']);
    // Route::get('/rich88/transfer/{transferNo}', [Rich88GameController::class, 'transferStatus']);
    // Route::get('/rich88/debug-balance', [Rich88GameController::class, 'debugBalance']);
    // Route::get('/rich88/check-account', [Rich88GameController::class, 'checkAccountMapping']);
    
    // // Bolegaming
    // Route::post('/bole/launch', [BoleGameController::class, 'launchGame']);
    // Route::get('/bole/balance', [BoleGameController::class, 'getPlayerBalance']);
    // Route::post('/bole/deposit', [BoleGameController::class, 'deposit']);
    // Route::get('/bole/game-records', [BoleGameController::class, 'getGameRecords']);
    // Route::post('/bole/sync-balance', [BoleGameController::class, 'syncBalance']);
    // Route::get('/bole/local-transactions', [BoleGameController::class, 'getLocalTransactions']);
    // Route::post('/bole/sync-from-bole', [BoleGameController::class, 'syncFromBole']);
    
    // // live 22
    // Route::post('/live/launch', [Live22GameController::class, 'launchGame']);
    
    
});


/* PP */
// Route::get("/game/list/pp", [PPPGameController::class, 'getGameList']);
// Route::get("/game/environments/pp", [PPPGameController::class, 'getEnvironments']);

/* King Kong */
/* 
Route::get('/kingkong/domains', [KingKongDomainController::class, 'getDomains']);
Route::post('/kingkong/domains/register', [KingKongDomainController::class, 'registerDomain']);
Route::post('/kingkong/domains/unregister', [KingKongDomainController::class, 'unregisterDomain']);
Route::post('/kingkong/callback/authenticate', [WebhookController::class, 'authenticateToken']);
*/

/* Call Back */
// Route::prefix('kingkong')->group(function () {
//     Route::post("/authenticate", [GameController::class, "authenticate"]);
//     Route::post("/authenticate-token", [GameController::class, "authtoken"]);
//     Route::post("/balance", [GameController::class, "getbalance"]);
//     Route::post("/bet", [GameController::class, "bet"]);
//     Route::post("/settle-bet", [GameController::class, "settleBet"]);
//     Route::post("/cancel-bet", [GameController::class, "cancelBet"]);
//     Route::post("/bonus-win", [GameController::class, "bonusWin"]);
//     Route::post("/jackpot-win", [GameController::class, "jackpotWin"]);
//     Route::post("/withdraw", [GameController::class, "fishingWithdraw"]);
//     Route::post("/deposit", [GameController::class, "fishingDeposit"]);
//     Route::post("/transaction", [GameController::class, "fishingTransaction"]);
//     Route::post("/join-tournament", [GameController::class, "joinTournament"]);
//     Route::post("/cancel-join-tournament", [GameController::class, "cancelJoinTournament"]);
//     Route::post("/win-tournament", [GameController::class, "winTournament"]);
// });
// Route::prefix('jili')->group(function () {
//     Route::post('/auth', [JiliGameController::class, 'jiliAuth']);
//     Route::post('/memberinfo', [JiliGameController::class, 'jiliMemberInfo']);
//     Route::post('/bet', [JiliGameController::class, 'bet']);
// });


// Route::prefix('pp')->group(function () {
//     // Launch endpoints
//     Route::get('/testlaunch', [PPPGameController::class, 'testLaunchPP']);
//     Route::get('/games', [PPPGameController::class, 'getCasinoGames']);
//     // Callback endpoints (exactly as PP expects)
//     Route::post('/authenticate.html', [PPPGameController::class, 'authenticate']);
//     Route::post('/balance.html', [PPPGameController::class, 'balance']);
//     Route::post('/bet.html', [PPPGameController::class, 'bet']);
//     Route::post('/result.html', [PPPGameController::class, 'result']);
//     Route::post('/refund.html', [PPPGameController::class, 'refund']);
//     Route::post('/endround.html', [PPPGameController::class, 'endRound']);
//     Route::post('/bonuswin.html', [PPPGameController::class, 'bonusWin']);
//     Route::post('/jackpotwin.html', [PPPGameController::class, 'jackpotWin']);
//     Route::post('/adjustment.html', [PPPGameController::class, 'adjustment']);
// });


// Buffalo Game CallBack Routes
// Route::prefix('buffalo')->group(function () {
//     // Callback endpoints that Buffalo will call
//     Route::post('get-player-balance', [BuffaloController::class, 'getUserBalance']);
//     Route::post('notify-amount-change', [BuffaloController::class, 'notifyAmountChange']);
    
//     // Your internal endpoints for fetching data
//     Route::get('bet-history', [BuffaloController::class, 'getBetHistory']);
//     Route::get('bet-result/{transactionId}', [BuffaloController::class, 'getBetResult']);
//     Route::get('games', [BuffaloController::class, 'getGameList']);
//       Route::post('sync-games', [BuffaloController::class, 'syncGameList']);
    
//     // Admin routes (protected)
//     Route::middleware('auth:sanctum')->group(function () {
//         Route::get('transactions', [BuffaloController::class, 'transactionHistory']);
      
//     });
// });

// CallBack
// AWC Routes
// Route::prefix('awc')->group(function () {
//     // Callback endpoint (already added - good!)
//     // Route::post('/api/awc', [AWCCallbackController::class, 'handle']);
    
//     // Game launch endpoints
//     Route::post('/launch', [AWCGameController::class, 'launchGame'])->middleware('auth:sanctum');
//     Route::post('/launch/{platform}', [AWCGameController::class, 'launchGameByPlatform'])->middleware('auth:sanctum');
//     Route::post('/launch/{platform}/{gameCode}', [AWCGameController::class, 'launchSpecificGame'])->middleware('auth:sanctum');
    
//     // Member management (admin only)
//     Route::middleware('auth:sanctum')->group(function () {

//     });
    
//     // Game list endpoints
//     Route::get('/platforms', [AWCGameController::class, 'getPlatforms']);
//     Route::get('/games', [AWCGameController::class, 'getGameLobby']); // Redirects to AWC lobby
// });


/* Rich88 */
// Route::prefix('rich/rich88')->group(function () {
//     Route::get('/games', [Rich88GameController::class, 'getGameList']);
//     Route::get('/games/db', [Rich88GameController::class, 'getGamesFromDb']);
//     Route::get('/session_id', [Rich88GameController::class, 'getSessionId']);
//     Route::get('/balance/{account}', [Rich88GameController::class, 'singleWalletBalance']);
//     Route::post('/transfer', [Rich88GameController::class, 'singleWalletTransfer']);
// });



// Liv22
// Route::prefix('/live22/callback')->group(function () {
//     Route::post('/',      [Live22CallbackController::class, 'handleCallBack']);
//     Route::post('GetBalance',      [Live22CallbackController::class, 'getBalance']);
//     Route::post('Bet',             [Live22CallbackController::class, 'bet']);
//     Route::post('GameResult',      [Live22CallbackController::class, 'gameResult']);
//     Route::post('Rollback',        [Live22CallbackController::class, 'rollback']);
//     Route::post('CashBonus',       [Live22CallbackController::class, 'cashBonus']);
//     Route::post('BetNSettle',      [Live22CallbackController::class, 'betNSettle']);
//     Route::post('CancelBetNSettle',[Live22CallbackController::class, 'cancelBetNSettle']);
// });


// bolegaming
// Route::prefix('card')->group(function () {
//     Route::post('/player/balance', [BoleWebhookController::class, 'handleBalance']);
//     Route::post('/player/cost', [BoleWebhookController::class, 'handleCost']);
//     #Route::get('/transactions', [BoleWebhookController::class, 'getTransactions']);
// });






// Route::post('/import-games', function (Request $request) {

//     if (!$request->hasFile('file')) {
//         return response()->json(['error' => 'No file uploaded'], 400);
//     }

//     $file = $request->file('file');

//     if (!$file->isValid()) {
//         return response()->json(['error' => 'Invalid file'], 400);
//     }

//     // Get file extension
//     $extension = $file->getClientOriginalExtension();
    
//     // Handle Excel files if uploaded as .xlsx
//     if ($extension === 'xlsx' || $extension === 'xls') {
//         return response()->json(['error' => 'Please save the file as CSV UTF-8 format. In Excel: File → Save As → CSV UTF-8 (Comma delimited)'], 400);
//     }

//     // Detect and handle different encodings
//     $content = file_get_contents($file->getRealPath());
    
//     // Try to detect encoding
//     $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    
//     // Convert to UTF-8 if needed
//     if ($encoding && $encoding !== 'UTF-8') {
//         $content = mb_convert_encoding($content, 'UTF-8', $encoding);
//     }
    
//     // Remove BOM if present
//     if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
//         $content = substr($content, 3);
//     }
    
//     // Create temporary file with UTF-8 content
//     $tempPath = tempnam(sys_get_temp_dir(), 'csv_');
//     file_put_contents($tempPath, $content);
//     $handle = fopen($tempPath, 'r');
    
//     if (!$handle) {
//         return response()->json(['error' => 'Failed to open temporary file'], 500);
//     }
    
//     $importCount = 0;
//     $errorCount = 0;
//     $errors = [];

//     // Read header
//     $header = fgetcsv($handle);

//     while (($row = fgetcsv($handle)) !== false) {
        
//         // Skip empty rows
//         if (empty(array_filter($row))) {
//             continue;
//         }
        
//         // Sanitize each field to ensure UTF-8
//         $row = array_map(function($field) {
//             // Remove any non-printable characters except basic ones
//             $field = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $field);
            
//             if (!mb_check_encoding($field, 'UTF-8')) {
//                 $field = mb_convert_encoding($field, 'UTF-8', 'auto');
//             }
//             return trim($field);
//         }, $row);
        
//         // Map columns based on your CSV structure
//         $gameName = $row[0] ?? null;           // Name
//         $chineseName = $row[1] ?? null;        // Chinese Name (Simplified)
//         $gameCode = $row[2] ?? null;           // Game Code
//         $gameType = $row[3] ?? null;           // GameType/游戏类型
//         $maxPlayers = $row[4] ?? null;         // Number of Players
//         $maxWin = $row[5] ?? null;             // Maximum Win
//         $languages = $row[6] ?? null;          // Languages
        
//         // Skip if no game code (required field)
//         if (empty($gameCode)) {
//             $errorCount++;
//             $errors[] = "Row has no Game Code: " . json_encode($row);
//             continue;
//         }
        
//         try {
//             // Combine name and Chinese name for game_name
//             $fullGameName = $gameName;
//             if ($chineseName && $chineseName !== $gameName) {
//                 $fullGameName = $gameName . ' - ' . $chineseName;
//             }
            
//             Game::updateOrCreate(
//                 ['game_code' => $gameCode],
//                 [
//                     'game_provider'     => 'Bolegaming',
//                     'game_type'         => $gameType ?? 'Unknown',
//                     'game_name'         => $fullGameName ?? $gameCode,
//                     'game_alias'        => $gameName ?? null,
//                     'game_specail'      => json_encode([
//                         'chinese_name' => $chineseName,
//                         'max_players' => $maxPlayers,
//                         'max_win_multiplier' => $maxWin,
//                         'languages' => $languages
//                     ]),
//                     'game_ggr_fee'      => 0,
//                     'free_spin'         => 0,
//                     'status'            => 1,
//                     'order'             => 0,
//                     'provider_game_id'  => $gameCode,
//                 ]
//             );
//             $importCount++;
            
//         } catch (\Exception $e) {
//             $errorCount++;
//             $errors[] = "Error importing game_code '{$gameCode}': " . $e->getMessage();
//         }
//     }

//     // Clean up
//     fclose($handle);
//     if (file_exists($tempPath)) {
//         unlink($tempPath);
//     }

//     return response()->json([
//         'message' => "Import completed",
//         'imported' => $importCount,
//         'errors' => $errorCount,
//         'error_details' => $errors
//     ]);
// });


// AWC 
// Route::post('/awc', [AWCCallbackController::class, 'handle']); 


/* ========== CQ9 Game Integration ========== */
// Route::prefix('cq9')->group(function () {
//     // Slot/Arcade endpoints
//     Route::get('/player/check/{account}', [CQ9GameController::class, 'checkPlayer']);
//     Route::get('/transaction/balance/{account}', [CQ9GameController::class, 'getBalance']);
//     Route::post('/transaction/game/bet', [CQ9GameController::class, 'bet']);
//     Route::post('/transaction/game/endround', [CQ9GameController::class, 'endRound']);
//     Route::post('/transaction/game/refund', [CQ9GameController::class, 'refund']);
    
//     // Table/Fishing/Arcade endpoints
//     Route::post('/transaction/game/rollout', [CQ9GameController::class, 'rollout']);
//     Route::post('/transaction/game/rollin', [CQ9GameController::class, 'rollin']);
//     Route::post('/transaction/game/takeall', [CQ9GameController::class, 'takeAll']);
//     Route::post('/transaction/game/debit', [CQ9GameController::class, 'debit']);
//     Route::post('/transaction/game/credit', [CQ9GameController::class, 'credit']);
//     Route::post('/transaction/user/payoff', [CQ9GameController::class, 'payoff']);
    
//     // Utility
//     Route::get('/games', [CQ9GameController::class, 'getGames']);
//     Route::get('/sync-games', [CQ9GameController::class, 'syncGames']);
// });

// GameBoy API (requires user auth)
// Route::prefix('cq9/gameboy')->middleware('auth:sanctum')->group(function () {
//     Route::post('/launch', [CQ9GameController::class, 'launchGame']);
//     Route::post('/lobby', [CQ9GameController::class, 'launchLobby']);
//     Route::post('/logout', [CQ9GameController::class, 'logout']);
//     Route::get('/balance/{account}', [CQ9GameController::class, 'getBalanceInternal']);
// });


// Route::prefix('joker')->group(function () {
    
//     // ============================================
//     // OPERATOR INTEGRATION APIS (Called by Joker)
//     // These MUST be publicly accessible without auth
//     // ============================================
    
//     // Authentication
//     Route::post('/authenticate-token', [JokerGameController::class, 'authenticateToken']);
//     Route::post('/authenticate', [JokerGameController::class, 'authenticate']);
    
//     // Balance
//     Route::post('/balance', [JokerGameController::class, 'balance']);
    
//     // Gameplay - Slot & Live Casino
//     Route::post('/bet', [JokerGameController::class, 'bet']);
//     Route::post('/settle-bet', [JokerGameController::class, 'settleBet']);
//     Route::post('/cancel-bet', [JokerGameController::class, 'cancelBet']);
//     Route::post('/bonus-win', [JokerGameController::class, 'bonusWin']);
//     Route::post('/jackpot-win', [JokerGameController::class, 'jackpotWin']);
    
//     // Gameplay - Fishing
//     Route::post('/transaction', [JokerGameController::class, 'transaction']);
//     Route::post('/withdraw', [JokerGameController::class, 'withdraw']);
//     Route::post('/deposit', [JokerGameController::class, 'deposit']);
    
//     // Tournament APIs
//     Route::post('/join-tournament', [JokerGameController::class, 'joinTournament']);
//     Route::post('/cancel-join-tournament', [JokerGameController::class, 'cancelJoinTournament']);
//     Route::post('/win-tournament', [JokerGameController::class, 'winTournament']);
//     Route::get('/sync-games', [JokerGameController::class, 'syncGames']);
    
//     // ============================================
//     // PROVIDER APIS (Called by your system to Joker)
//     // These require authentication
//     // ============================================
    
//     Route::middleware('auth:sanctum')->group(function () {
//         // Game management
        
//         Route::get('/games', [JokerGameController::class, 'getGames']);
//         Route::post('/launch', [JokerGameController::class, 'launchGame']);
        
//         // Balance check from Joker
//         Route::get('/player-balance/{username}', [JokerGameController::class, 'getPlayerBalanceFromJoker']);
        
//         // Transaction history for debugging
//         Route::get('/transactions/{userId}', [JokerGameController::class, 'getUserTransactions']);
//     });
// });


// In routes/api.php or web.php
Route::prefix('football')->group(function () {
    Route::get('sync-all', [FootballController::class, 'get_the_match']);
    Route::get('sync-match/{fixtureId}', [FootballController::class, 'syncSingleMatch']);
    Route::get('sync-upcoming', [FootballController::class, 'syncUpcomingMatches']);
});

// Football match routes for frontend
Route::prefix('football')->group(function () {
    Route::get('/get_the_football_match', [FootballAPIController::class, 'get_the_football_match']);
    Route::get('/match/{id}', [FootballAPIController::class, 'getMatchById']);
    Route::get('/matches-by-date', [FootballAPIController::class, 'getMatchesByDate']);
    Route::get('/today-matches', [FootballAPIController::class, 'getTodaysMatches']);
    Route::get('/leagues', [FootballAPIController::class, 'getLeagues']);
    Route::get('/league/{leagueName}', [FootballAPIController::class, 'getMatchesByLeague']);
});


Route::middleware('auth:sanctum')->group(function () {
    // Betting Routes
    Route::prefix('betting')->group(function () {
        // Place bets
        Route::post('/single', [BettingController::class, 'placeSingleBet']);
        Route::post('/multi', [BettingController::class, 'placeMultiBet']);
        // Get bets
        Route::get('/slip/{slipNumber}', [BettingController::class, 'getBettingSlip']);
        Route::get('/history', [BettingController::class, 'getUserBets']);
        Route::get('/statistics', [BettingController::class, 'getBettingStatistics']);
        // Cancel bet
        Route::post('/cancel/{slipNumber}', [BettingController::class, 'cancelBet']);
        // Calculations
        Route::post('/calculate/single', [BettingController::class, 'calculatePotentialWin']);
        Route::post('/calculate/multi', [BettingController::class, 'calculateMultiBetWin']);
        // Settings
        Route::get('/limits', [BettingController::class, 'getBettingLimits']);
    });
});


// Route::post('/new_buffalo_balance', [Buffalo789Controller::class,"check_one"]);

// Route::post('/new_buffalo', [Buffalo789Controller::class,"check_two"]);

