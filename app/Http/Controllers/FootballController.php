<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\FootballMatch;

class FootballController extends Controller
{
    /**
     * Sync matches from both fixtures/all and fixtures/detail endpoints
     */
    public function get_the_match()
    {
        try {
            // Step 1: Get all fixture IDs from /fixtures/all
            $fixtureIds = $this->fetchAllFixtureIds();
            
            // if (empty($fixtureIds)) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'No fixture IDs found from API',
            //     ], 400);
            // }
            
            $savedCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $errors = [];
            
            // Step 2: Fetch details for each fixture ID
            foreach ($fixtureIds as $fixtureId) {
                try {
                    $matchData = $this->fetchFixtureDetail($fixtureId);
                    
                    if (!$matchData) {
                        $skippedCount++;
                        $errors[] = [
                            'match_id' => $fixtureId,
                            'error' => 'Failed to fetch match details'
                        ];
                        continue;
                    }
                    
                    // Step 3: Save or update match
                    $result = $this->saveMatchData($matchData);
                    
                    if ($result['action'] === 'saved') {
                        $savedCount++;
                    } elseif ($result['action'] === 'updated') {
                        $updatedCount++;
                    }
                    
                    Log::info("Match {$result['action']}", [
                        'match_id' => $fixtureId,
                        'host' => $matchData['host_team_data']['name_en'] ?? 'Unknown',
                        'guest' => $matchData['guest_team_data']['name_en'] ?? 'Unknown'
                    ]);
                    
                } catch (\Exception $e) {
                    $skippedCount++;
                    $errors[] = [
                        'match_id' => $fixtureId,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to process match', [
                        'match_id' => $fixtureId,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Optional: Add a small delay to avoid rate limiting
                usleep(100000); // 0.1 second delay
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Matches synced successfully',
                'data' => [
                    'total_fixtures' => count($fixtureIds),
                    'new_saved' => $savedCount,
                    'updated' => $updatedCount,
                    'skipped' => $skippedCount,
                    'errors' => $errors
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in get_the_match: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing matches',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Alternative: Sync a single match by ID
     */
    public function syncSingleMatch($fixtureId)
    {
        try {
            $matchData = $this->fetchFixtureDetail($fixtureId);
            
            if (!$matchData) {
                return response()->json([
                    'success' => false,
                    'message' => "Failed to fetch match data for ID: {$fixtureId}",
                ], 400);
            }
            
            $result = $this->saveMatchData($matchData);
            
            return response()->json([
                'success' => true,
                'message' => "Match {$result['action']} successfully",
                'data' => [
                    'match_id' => $fixtureId,
                    'action' => $result['action'],
                    'match' => $matchData['host_team_data']['name_en'] . ' vs ' . $matchData['guest_team_data']['name_en']
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error syncing match {$fixtureId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Fetch all fixture IDs from the /fixtures/all endpoint
     */
    private function fetchAllFixtureIds()
    {
        try {
            // You might need to adjust this based on the actual API requirements
            // The current response shows {"msg":"Hello Soccer Home"} which suggests
            // the endpoint might need specific headers or parameters
            
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://be.3xscores.net/api/soccer/fixtures/all', [
                // Add any required parameters here based on API docs
                // 'date' => date('Y-m-d'),
                // 'league_id' => etc.
            ]);
            
            if (!$response->successful()) {
                Log::error('Failed to fetch fixture list', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }
            
            $data = $response->json();
            
            // Handle different response structures
            if (isset($data['data']) && is_array($data['data'])) {
                // If response contains array of fixtures with IDs
                return array_map(function($fixture) {
                    return $fixture['id'] ?? null;
                }, $data['data']);
            } elseif (isset($data['fixtures']) && is_array($data['fixtures'])) {
                return array_map(function($fixture) {
                    return $fixture['id'] ?? null;
                }, $data['fixtures']);
            } elseif (isset($data['ids']) && is_array($data['ids'])) {
                return $data['ids'];
            }
            
            // If the endpoint doesn't return fixture list, you might need to
            // fetch from another source or maintain a list of fixture IDs
            Log::warning('Unexpected response structure from fixtures/all', ['response' => $data]);
            
            // Fallback: Return some hardcoded IDs or fetch from database
            return $this->getFixtureIdsFromDatabase();
            
        } catch (\Exception $e) {
            Log::error('Exception fetching fixture IDs: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Fetch detailed match data from /fixtures/detail/{id}
     */
    private function fetchFixtureDetail($fixtureId)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("https://be.3xscores.net/api/soccer/fixtures/detail/{$fixtureId}");
            
            if (!$response->successful()) {
                Log::error("Failed to fetch fixture detail for ID: {$fixtureId}", [
                    'status' => $response->status()
                ]);
                return null;
            }
            
            $data = $response->json();
            
            // Check if response status is success
            if (($data['status'] ?? '') !== 'success' && !isset($data['data'])) {
                Log::error("API returned error for ID: {$fixtureId}", [
                    'response' => $data
                ]);
                return null;
            }
            
            // Extract match data from response
            $matchData = $data['data'] ?? null;
            
            if (!$matchData) {
                Log::error("No match data found for ID: {$fixtureId}");
                return null;
            }
            
            return $matchData;
            
        } catch (\Exception $e) {
            Log::error("Exception fetching fixture detail for {$fixtureId}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save or update match data in database (prevents duplicates)
     */
    private function saveMatchData($matchData)
    {
        // Extract odds data
        $firstHalfOdds = $matchData['odds']['first_half'] ?? [];
        $fullTimeOdds = $matchData['odds']['full_time'] ?? [];
        
        // Process correct scores (remove duplicates if any)
        $fullTimeScores = $this->processCorrectScores($matchData['correct_scores']['full_time'] ?? []);
        
        $saveData = [
            'id' => (string) $matchData['id'],
            'host_team_name_en' => $matchData['host_team_data']['name_en'] ?? null,
            'host_team_name_mm' => $matchData['host_team_data']['name_mm'] ?? null,
            'host_team_code' => $matchData['host_team_data']['code'] ?? null,
            'guest_team_name_en' => $matchData['guest_team_data']['name_en'] ?? null,
            'guest_team_name_mm' => $matchData['guest_team_data']['name_mm'] ?? null,
            'guest_team_code' => $matchData['guest_team_data']['code'] ?? null,
            'league_name_en' => $matchData['league_data']['name_en'] ?? null,
            'league_name_mm' => $matchData['league_data']['name_mm'] ?? null,
            'league_code' => $matchData['league_data']['code'] ?? null,
            'fixture_timestamp' => (int) ($matchData['fixture_timestamp'] ?? 0),
            'fixture_start_time' => isset($matchData['fixture_timestamp']) 
                ? date('Y-m-d H:i:s', (int) $matchData['fixture_timestamp'])
                : null,
            'is_popular_match' => filter_var($matchData['is_popular_match'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_perlay' => filter_var($matchData['is_perlay'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'fh_odds_team' => $firstHalfOdds['odds_team'] ?? null,
            'fh_odd' => $firstHalfOdds['odd'] ?? 0,
            'fh_even' => $firstHalfOdds['even'] ?? 0,
            'fh_one' => $firstHalfOdds['one'] ?? 0,
            'fh_x' => $firstHalfOdds['x'] ?? 0,
            'fh_two' => $firstHalfOdds['two'] ?? 0,
            'fh_hdp_mm_odds' => $firstHalfOdds['hdp_mm_odds'] ?? null,
            'fh_ou_mm_odds' => $firstHalfOdds['ou_mm_odds'] ?? null,
            'ft_odds_team' => $fullTimeOdds['odds_team'] ?? null,
            'ft_odd' => $fullTimeOdds['odd'] ?? 0,
            'ft_even' => $fullTimeOdds['even'] ?? 0,
            'ft_one' => $fullTimeOdds['one'] ?? 0,
            'ft_x' => $fullTimeOdds['x'] ?? 0,
            'ft_two' => $fullTimeOdds['two'] ?? 0,
            'ft_hdp_mm_odds' => $fullTimeOdds['hdp_mm_odds'] ?? null,
            'ft_ou_mm_odds' => $fullTimeOdds['ou_mm_odds'] ?? null,
            'fh_correct_scores' => $matchData['correct_scores']['first_half'] ?? [],
            'ft_correct_scores' => $fullTimeScores,
            'created_at_mm' => isset($matchData['created_at_mm']) 
                ? date('Y-m-d H:i:s', strtotime($matchData['created_at_mm']))
                : null,
        ];
        
        // Check if match already exists
        $existingMatch = FootballMatch::find($saveData['id']);
        
        if ($existingMatch) {
            // Update existing match
            $existingMatch->update($saveData);
            return ['action' => 'updated', 'match_id' => $saveData['id']];
        } else {
            // Create new match
            FootballMatch::create($saveData);
            return ['action' => 'saved', 'match_id' => $saveData['id']];
        }
    }
    
    /**
     * Process correct scores to remove duplicates
     */
    private function processCorrectScores($scores)
    {
        if (empty($scores)) {
            return [];
        }
        
        $unique = [];
        $seen = [];
        
        foreach ($scores as $score) {
            $key = $score['name'] . '_' . ($score['header'] ?? '');
            if (!in_array($key, $seen)) {
                $seen[] = $key;
                $unique[] = $score;
            }
        }
        
        return $unique;
    }
    
    /**
     * Fallback method to get fixture IDs from database or predefined list
     */
    private function getFixtureIdsFromDatabase()
    {
        // If the /fixtures/all endpoint doesn't work, you can:
        // 1. Maintain a list of fixture IDs to sync
        // 2. Fetch IDs from your database that need updates
        // 3. Use a predefined range
        
        // Example: Fetch existing IDs from database to update them
        $existingIds = FootballMatch::pluck('id')->toArray();
        
        if (!empty($existingIds)) {
            return $existingIds;
        }
        
        // Fallback: Return some known fixture IDs
        // You should replace this with actual IDs from your API documentation
        return ['2977', '2978']; // Example IDs from your response
    }
    
    /**
     * Sync only upcoming matches (based on timestamp)
     */
    public function syncUpcomingMatches()
    {
        try {
            // Fetch all fixture IDs first
            $fixtureIds = $this->fetchAllFixtureIds();
            
            if (empty($fixtureIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fixture IDs found'
                ], 400);
            }
            
            $syncedCount = 0;
            
            foreach ($fixtureIds as $fixtureId) {
                $matchData = $this->fetchFixtureDetail($fixtureId);
                
                if ($matchData && isset($matchData['fixture_timestamp'])) {
                    $matchTime = (int) $matchData['fixture_timestamp'];
                    $currentTime = time();
                    
                    // Only sync matches that are in the future or within last 2 hours
                    if ($matchTime > ($currentTime - 7200)) {
                        $this->saveMatchData($matchData);
                        $syncedCount++;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Synced {$syncedCount} upcoming matches"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}