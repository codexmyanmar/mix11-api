<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchResult extends Model
{
    use HasFactory;

    protected $table = 'match_results';

    protected $fillable = [
        'match_id',
        'host_team_name',
        'guest_team_name',
        'host_score',
        'guest_score',
        'full_time_result',
        'half_time_host_score',
        'half_time_guest_score',
        'half_time_result',
        'total_goals',
        'is_odd',
        'match_status',
        'result_confirmed_at',
    ];

    protected $casts = [
        'host_score' => 'integer',
        'guest_score' => 'integer',
        'half_time_host_score' => 'integer',
        'half_time_guest_score' => 'integer',
        'total_goals' => 'integer',
        'is_odd' => 'boolean',
        'result_confirmed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'match_status' => 'upcoming',
        'host_score' => 0,
        'guest_score' => 0,
        'half_time_host_score' => 0,
        'half_time_guest_score' => 0,
        'total_goals' => 0,
    ];

    // Relationships
    public function footballMatch()
    {
        return $this->belongsTo(FootballMatch::class, 'match_id', 'id');
    }

    public function bettingSelections()
    {
        return $this->hasMany(BettingSelection::class, 'match_id', 'match_id');
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('match_status', 'upcoming');
    }

    public function scopeLive($query)
    {
        return $query->where('match_status', 'live');
    }

    public function scopeCompleted($query)
    {
        return $query->where('match_status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('match_status', 'cancelled');
    }

    public function scopePostponed($query)
    {
        return $query->where('match_status', 'postponed');
    }

    // Helper Methods
    public function isUpcoming()
    {
        return $this->match_status === 'upcoming';
    }

    public function isLive()
    {
        return $this->match_status === 'live';
    }

    public function isCompleted()
    {
        return $this->match_status === 'completed';
    }

    public function isCancelled()
    {
        return $this->match_status === 'cancelled';
    }

    public function isPostponed()
    {
        return $this->match_status === 'postponed';
    }

    public function getFullTimeResultTextAttribute()
    {
        $results = [
            'home' => $this->host_team_name,
            'draw' => 'Draw',
            'away' => $this->guest_team_name,
        ];
        return $results[$this->full_time_result] ?? 'Unknown';
    }

    public function getHalfTimeResultTextAttribute()
    {
        $results = [
            'home' => $this->host_team_name,
            'draw' => 'Draw',
            'away' => $this->guest_team_name,
        ];
        return $results[$this->half_time_result] ?? 'Unknown';
    }

    public function isMatchWinner($team)
    {
        if ($team === 'home') {
            return $this->full_time_result === 'home';
        } elseif ($team === 'away') {
            return $this->full_time_result === 'away';
        }
        return false;
    }

    public function isDraw()
    {
        return $this->full_time_result === 'draw';
    }

    public function updateFromFootballMatch(FootballMatch $match)
    {
        $this->update([
            'host_team_name' => $match->host_team_name_en,
            'guest_team_name' => $match->guest_team_name_en,
            'match_status' => $match->fixture_start_time > now() ? 'upcoming' : 'completed',
        ]);
    }
}