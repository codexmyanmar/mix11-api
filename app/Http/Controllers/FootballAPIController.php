<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\FootballMatch;

class FootballAPIController extends Controller
{
    /**
     * Get all football matches for frontend
     */
public function get_the_football_match(Request $request)
{
    try {
        $query = FootballMatch::query();
        
        // Add date range filter - between yesterday and tomorrow
        $yesterday = now()->utc()->subDay()->startOfDay();
        $tomorrow = now()->utc()->addDay()->endOfDay();
        $query->whereBetween('fixture_start_time', [$yesterday, $tomorrow]);
        
        if ($request->has('league')) {
            $query->where('league_name_en', 'LIKE', '%' . $request->league . '%')
                  ->orWhere('league_name_mm', 'LIKE', '%' . $request->league . '%');
        }
        
        if ($request->has('team')) {
            $query->where(function($q) use ($request) {
                $q->where('host_team_name_en', 'LIKE', '%' . $request->team . '%')
                  ->orWhere('host_team_name_mm', 'LIKE', '%' . $request->team . '%')
                  ->orWhere('guest_team_name_en', 'LIKE', '%' . $request->team . '%')
                  ->orWhere('guest_team_name_mm', 'LIKE', '%' . $request->team . '%');
            });
        }
        
        if ($request->has('status')) {
            switch ($request->status) {
                case 'upcoming':
                    $query->where('fixture_start_time', '>', now());
                    break;
                case 'live':
                    $query->where('fixture_start_time', '<=', now())
                          ->where('fixture_start_time', '>', now()->subHours(2));
                    break;
                case 'completed':
                    $query->where('fixture_start_time', '<', now()->subHours(2));
                    break;
            }
        }
        
        if ($request->has('popular') && $request->popular) {
            $query->where('is_popular_match', true);
        }
        
        if ($request->has('perlay') && $request->perlay) {
            $query->where('is_perlay', true);
        }
        
        $orderBy = $request->get('order_by', 'fixture_start_time');
        $orderDir = $request->get('order_dir', 'asc');
        $query->orderBy($orderBy, $orderDir);
        
        $perPage = $request->get('per_page', 20);
        $matches = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Matches fetched successfully',
            'data' => $this->formatMatchesForFrontend($matches->items()),
            'meta' => [
                'current_page' => $matches->currentPage(),
                'per_page' => $matches->perPage(),
                'total' => $matches->total(),
                'last_page' => $matches->lastPage(),
            ]
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error fetching matches: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'Failed to fetch matches',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Get single match by ID
     */
    public function getMatchById($id)
    {
        try {
            $match = FootballMatch::find($id);
            
            if (!$match) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Match not found'
                ], 404);
            }
            
            $formattedMatch = $this->formatSingleMatch($match);
            
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Match fetched successfully',
                'data' => $formattedMatch
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching match: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to fetch match'
            ], 500);
        }
    }
    
    /**
     * Get matches by date range
     */
    public function getMatchesByDate(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->startOfDay());
            $endDate = $request->get('end_date', now()->addDays(7)->endOfDay());
            
            $matches = FootballMatch::whereBetween('fixture_start_time', [$startDate, $endDate])
                ->orderBy('fixture_start_time', 'asc')
                ->get();
                
            $formattedMatches = $this->formatMatchesForFrontend($matches);
            
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Matches fetched successfully',
                'data' => $formattedMatches,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching matches by date: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to fetch matches'
            ], 500);
        }
    }
    
    /**
     * Get unique leagues list
     */
    public function getLeagues()
    {
        try {
            $leagues = FootballMatch::select('league_name_en', 'league_name_mm', 'league_code')
                ->distinct()
                ->whereNotNull('league_name_en')
                ->orderBy('league_name_en')
                ->get();
                
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Leagues fetched successfully',
                'data' => $leagues
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching leagues: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to fetch leagues'
            ], 500);
        }
    }
    
    /**
     * Get today's matches
     */
    public function getTodaysMatches()
    {
        try {
            $today = now()->startOfDay();
            $tomorrow = now()->endOfDay();
            
            $matches = FootballMatch::whereBetween('fixture_start_time', [$today, $tomorrow])
                ->orderBy('fixture_start_time', 'asc')
                ->get();
                
            $formattedMatches = $this->formatMatchesForFrontend($matches);
            
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Today\'s matches fetched successfully',
                'data' => $formattedMatches,
                'date' => now()->toDateString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching today\'s matches: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to fetch today\'s matches'
            ], 500);
        }
    }
    
    /**
     * Get matches by league
     */
    public function getMatchesByLeague($leagueName)
    {
        try {
            $matches = FootballMatch::where('league_name_en', 'LIKE', '%' . $leagueName . '%')
                ->orWhere('league_name_mm', 'LIKE', '%' . $leagueName . '%')
                ->orderBy('fixture_start_time', 'asc')
                ->paginate(20);
                
            $formattedMatches = $this->formatMatchesForFrontend($matches->items());
            
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Matches fetched successfully',
                'data' => $formattedMatches,
                'league' => $leagueName,
                'meta' => [
                    'current_page' => $matches->currentPage(),
                    'per_page' => $matches->perPage(),
                    'total' => $matches->total(),
                    'last_page' => $matches->lastPage(),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching matches by league: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to fetch matches'
            ], 500);
        }
    }
    
    /**
     * Format matches for frontend response
     */
    private function formatMatchesForFrontend($matches)
    {
        return array_map(function($match) {
            return $this->formatSingleMatch($match);
        }, $matches);
    }
    
    /**
     * Format single match for frontend response
     */
    private function formatSingleMatch($match)
    {
        return [
            'id' => $match->id,
            'host_team_data' => [
                'name_en' => $match->host_team_name_en,
                'name_mm' => $match->host_team_name_mm,
                'code' => $match->host_team_code
            ],
            'guest_team_data' => [
                'name_en' => $match->guest_team_name_en,
                'name_mm' => $match->guest_team_name_mm,
                'code' => $match->guest_team_code
            ],
            'league_data' => [
                'name_en' => $match->league_name_en,
                'name_mm' => $match->league_name_mm,
                'code' => $match->league_code
            ],
            'fixture_timestamp' => $match->fixture_timestamp,
            'fixture_start_time' => $match->fixture_start_time,
            'is_popular_match' => (bool) $match->is_popular_match,
            'is_perlay' => (bool) $match->is_perlay,
            'odds' => [
                'first_half' => [
                    'football_fixture_id' => $match->id,
                    'odds_team' => $match->fh_odds_team,
                    'odd' => $match->fh_odd,
                    'even' => $match->fh_even,
                    'one' => $match->fh_one,
                    'x' => $match->fh_x,
                    'two' => $match->fh_two,
                    'match_stage' => 'first_half',
                    'hdp_mm_odds' => $match->fh_hdp_mm_odds,
                    'ou_mm_odds' => $match->fh_ou_mm_odds
                ],
                'full_time' => [
                    'football_fixture_id' => $match->id,
                    'odds_team' => $match->ft_odds_team,
                    'odd' => $match->ft_odd,
                    'even' => $match->ft_even,
                    'one' => $match->ft_one,
                    'x' => $match->ft_x,
                    'two' => $match->ft_two,
                    'match_stage' => 'full_time',
                    'hdp_mm_odds' => $match->ft_hdp_mm_odds,
                    'ou_mm_odds' => $match->ft_ou_mm_odds
                ]
            ],
            'correct_scores' => [
                'first_half' => $match->fh_correct_scores ?? [],
                'full_time' => $match->ft_correct_scores ?? []
            ],
            'created_at_mm' => $match->created_at_mm,
            'match_status' => $this->getMatchStatus($match->fixture_start_time),
            'match_title' => $match->host_team_name_en . ' vs ' . $match->guest_team_name_en,
            'match_time_formatted' => date('Y-m-d H:i:s', $match->fixture_timestamp)
        ];
    }
    
    /**
     * Get match status (upcoming, live, completed)
     */
    private function getMatchStatus($fixtureStartTime)
    {
        $startTime = strtotime($fixtureStartTime);
        $now = time();
        
        if ($startTime > $now) {
            return 'upcoming';
        } elseif ($startTime <= $now && $startTime > ($now - 7200)) {
            return 'live';
        } else {
            return 'completed';
        }
    }
}