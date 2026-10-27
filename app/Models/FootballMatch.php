<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FootballMatch extends Model
{
    use HasFactory;

    protected $table = 'football_matches';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'host_team_name_en',
        'host_team_name_mm',
        'host_team_code',
        'guest_team_name_en',
        'guest_team_name_mm',
        'guest_team_code',
        'league_name_en',
        'league_name_mm',
        'league_code',
        'fixture_timestamp',
        'fixture_start_time',
        'is_popular_match',
        'is_perlay',
        'fh_odds_team',
        'fh_odd',
        'fh_even',
        'fh_one',
        'fh_x',
        'fh_two',
        'fh_hdp_mm_odds',
        'fh_ou_mm_odds',
        'ft_odds_team',
        'ft_odd',
        'ft_even',
        'ft_one',
        'ft_x',
        'ft_two',
        'ft_hdp_mm_odds',
        'ft_ou_mm_odds',
        'fh_correct_scores',
        'ft_correct_scores',
        'created_at_mm',
    ];

    protected $casts = [
        'fixture_timestamp' => 'integer',
        'fixture_start_time' => 'datetime',
        'is_popular_match' => 'boolean',
        'is_perlay' => 'boolean',
        'fh_odd' => 'float',
        'fh_even' => 'float',
        'fh_one' => 'float',
        'fh_x' => 'float',
        'fh_two' => 'float',
        'ft_odd' => 'float',
        'ft_even' => 'float',
        'ft_one' => 'float',
        'ft_x' => 'float',
        'ft_two' => 'float',
        'fh_correct_scores' => 'array',
        'ft_correct_scores' => 'array',
        'created_at_mm' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Accessors
    public function getHostTeamFullNameAttribute(): string
    {
        return $this->host_team_name_en ?: ($this->host_team_name_mm ?: 'N/A');
    }

    public function getGuestTeamFullNameAttribute(): string
    {
        return $this->guest_team_name_en ?: ($this->guest_team_name_mm ?: 'N/A');
    }

    public function getMatchTitleAttribute(): string
    {
        return "{$this->host_team_full_name} vs {$this->guest_team_full_name}";
    }

    public function getFirstHalfScoresAttribute()
    {
        return collect($this->fh_correct_scores ?? []);
    }

    public function getFullTimeScoresAttribute()
    {
        return collect($this->ft_correct_scores ?? []);
    }

    public function getUniqueFullTimeScoresAttribute()
    {
        if (!$this->ft_correct_scores) {
            return collect();
        }
        
        // Remove duplicates based on name
        return collect($this->ft_correct_scores)
            ->unique('name')
            ->values();
    }

    // Helper methods
    public function hasFirstHalfOdds(): bool
    {
        return $this->fh_one != 0 || $this->fh_x != 0 || $this->fh_two != 0;
    }

    public function hasFullTimeOdds(): bool
    {
        return $this->ft_one != 0 || $this->ft_x != 0 || $this->ft_two != 0;
    }

    public function getOdds(string $stage = 'full_time'): ?array
    {
        if ($stage === 'first_half') {
            return [
                'odds_team' => $this->fh_odds_team,
                'odd' => $this->fh_odd,
                'even' => $this->fh_even,
                'one' => $this->fh_one,
                'x' => $this->fh_x,
                'two' => $this->fh_two,
                'hdp_mm_odds' => $this->fh_hdp_mm_odds,
                'ou_mm_odds' => $this->fh_ou_mm_odds,
            ];
        }
        
        return [
            'odds_team' => $this->ft_odds_team,
            'odd' => $this->ft_odd,
            'even' => $this->ft_even,
            'one' => $this->ft_one,
            'x' => $this->ft_x,
            'two' => $this->ft_two,
            'hdp_mm_odds' => $this->ft_hdp_mm_odds,
            'ou_mm_odds' => $this->ft_ou_mm_odds,
        ];
    }

    public function getCorrectScoreOdds(string $score, string $stage = 'full_time'): ?float
    {
        $scores = $stage === 'first_half' ? $this->fh_correct_scores : $this->ft_correct_scores;
        
        if (!$scores) {
            return null;
        }
        
        $scoreData = collect($scores)->firstWhere('name', $score);
        
        return $scoreData['odds'] ?? null;
    }

    // Scopes
    public function scopePopular($query)
    {
        return $query->where('is_popular_match', true);
    }

    public function scopePerlay($query)
    {
        return $query->where('is_perlay', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('fixture_start_time', '>', now());
    }

    public function scopeLive($query)
    {
        return $query->where('fixture_start_time', '<=', now())
                     ->where('fixture_start_time', '>', now()->subHours(2));
    }

    public function scopeByLeague($query, string $leagueName)
    {
        return $query->where('league_name_en', 'LIKE', "%{$leagueName}%")
                     ->orWhere('league_name_mm', 'LIKE', "%{$leagueName}%");
    }

    public function scopeByTeam($query, string $teamName)
    {
        return $query->where(function ($q) use ($teamName) {
            $q->where('host_team_name_en', 'LIKE', "%{$teamName}%")
              ->orWhere('host_team_name_mm', 'LIKE', "%{$teamName}%")
              ->orWhere('guest_team_name_en', 'LIKE', "%{$teamName}%")
              ->orWhere('guest_team_name_mm', 'LIKE', "%{$teamName}%");
        });
    }

    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('fixture_start_time', [$startDate, $endDate]);
    }

    // API Sync Methods
    public static function updateOrCreateFromApi(array $matchData): self
    {
        $firstHalfOdds = $matchData['odds']['first_half'] ?? [];
        $fullTimeOdds = $matchData['odds']['full_time'] ?? [];
        
        // Process correct scores to remove duplicates
        $fullTimeScores = self::processCorrectScores($matchData['correct_scores']['full_time'] ?? []);
        
        return self::updateOrCreate(
            ['id' => (string) $matchData['id']],
            [
                'host_team_name_en' => $matchData['host_team_data']['name_en'] ?? null,
                'host_team_name_mm' => $matchData['host_team_data']['name_mm'] ?? null,
                'host_team_code' => $matchData['host_team_data']['code'] ?? null,
                'guest_team_name_en' => $matchData['guest_team_data']['name_en'] ?? null,
                'guest_team_name_mm' => $matchData['guest_team_data']['name_mm'] ?? null,
                'guest_team_code' => $matchData['guest_team_data']['code'] ?? null,
                'league_name_en' => $matchData['league_data']['name_en'] ?? null,
                'league_name_mm' => $matchData['league_data']['name_mm'] ?? null,
                'league_code' => $matchData['league_data']['code'] ?? null,
                'fixture_timestamp' => (int) $matchData['fixture_timestamp'],
                'fixture_start_time' => Carbon::createFromTimestamp($matchData['fixture_timestamp']),
                'is_popular_match' => (bool) ($matchData['is_popular_match'] ?? false),
                'is_perlay' => (bool) ($matchData['is_perlay'] ?? false),
                'fh_odds_team' => $firstHalfOdds['odds_team'] ?? null,
                'fh_odd' => (float) ($firstHalfOdds['odd'] ?? 0),
                'fh_even' => (float) ($firstHalfOdds['even'] ?? 0),
                'fh_one' => (float) ($firstHalfOdds['one'] ?? 0),
                'fh_x' => (float) ($firstHalfOdds['x'] ?? 0),
                'fh_two' => (float) ($firstHalfOdds['two'] ?? 0),
                'fh_hdp_mm_odds' => $firstHalfOdds['hdp_mm_odds'] ?? null,
                'fh_ou_mm_odds' => $firstHalfOdds['ou_mm_odds'] ?? null,
                'ft_odds_team' => $fullTimeOdds['odds_team'] ?? null,
                'ft_odd' => (float) ($fullTimeOdds['odd'] ?? 0),
                'ft_even' => (float) ($fullTimeOdds['even'] ?? 0),
                'ft_one' => (float) ($fullTimeOdds['one'] ?? 0),
                'ft_x' => (float) ($fullTimeOdds['x'] ?? 0),
                'ft_two' => (float) ($fullTimeOdds['two'] ?? 0),
                'ft_hdp_mm_odds' => $fullTimeOdds['hdp_mm_odds'] ?? null,
                'ft_ou_mm_odds' => $fullTimeOdds['ou_mm_odds'] ?? null,
                'fh_correct_scores' => $matchData['correct_scores']['first_half'] ?? [],
                'ft_correct_scores' => $fullTimeScores,
                'created_at_mm' => isset($matchData['created_at_mm']) 
                    ? Carbon::createFromFormat('Y-m-d h:i:s a', $matchData['created_at_mm'])
                    : null,
            ]
        );
    }

    public static function updateFromDetailApi(array $detailData): self
    {
        $match = self::find($detailData['id']);
        
        if (!$match) {
            return self::updateOrCreateFromApi($detailData);
        }
        
        $firstHalfOdds = $detailData['odds']['first_half'] ?? [];
        $fullTimeOdds = $detailData['odds']['full_time'] ?? [];
        
        $fullTimeScores = self::processCorrectScores($detailData['correct_scores']['full_time'] ?? []);
        
        $match->update([
            'fh_odds_team' => $firstHalfOdds['odds_team'] ?? $match->fh_odds_team,
            'fh_odd' => (float) ($firstHalfOdds['odd'] ?? $match->fh_odd),
            'fh_even' => (float) ($firstHalfOdds['even'] ?? $match->fh_even),
            'fh_one' => (float) ($firstHalfOdds['one'] ?? $match->fh_one),
            'fh_x' => (float) ($firstHalfOdds['x'] ?? $match->fh_x),
            'fh_two' => (float) ($firstHalfOdds['two'] ?? $match->fh_two),
            'fh_hdp_mm_odds' => $firstHalfOdds['hdp_mm_odds'] ?? $match->fh_hdp_mm_odds,
            'fh_ou_mm_odds' => $firstHalfOdds['ou_mm_odds'] ?? $match->fh_ou_mm_odds,
            'ft_odds_team' => $fullTimeOdds['odds_team'] ?? $match->ft_odds_team,
            'ft_odd' => (float) ($fullTimeOdds['odd'] ?? $match->ft_odd),
            'ft_even' => (float) ($fullTimeOdds['even'] ?? $match->ft_even),
            'ft_one' => (float) ($fullTimeOdds['one'] ?? $match->ft_one),
            'ft_x' => (float) ($fullTimeOdds['x'] ?? $match->ft_x),
            'ft_two' => (float) ($fullTimeOdds['two'] ?? $match->ft_two),
            'ft_hdp_mm_odds' => $fullTimeOdds['hdp_mm_odds'] ?? $match->ft_hdp_mm_odds,
            'ft_ou_mm_odds' => $fullTimeOdds['ou_mm_odds'] ?? $match->ft_ou_mm_odds,
            'fh_correct_scores' => $detailData['correct_scores']['first_half'] ?? $match->fh_correct_scores,
            'ft_correct_scores' => $fullTimeScores,
        ]);
        
        return $match->fresh();
    }
    
    private static function processCorrectScores(array $scores): array
    {
        // Remove duplicates based on 'name' field
        $unique = [];
        $seen = [];
        
        foreach ($scores as $score) {
            if (!in_array($score['name'], $seen)) {
                $seen[] = $score['name'];
                $unique[] = $score;
            }
        }
        
        return $unique;
    }
}