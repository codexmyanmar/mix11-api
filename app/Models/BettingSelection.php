<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BettingSelection extends Model
{
    use HasFactory;

    protected $table = 'betting_selections';

    protected $fillable = [
        'slip_id',
        'slip_number',
        'user_id',
        'match_id',
        'match_timestamp',
        'match_start_time',
        'host_team_name',
        'guest_team_name',
        'league_name',
        'bet_market',
        'bet_selection',
        'odds',
        'odds_value',
        'result_status',
        'actual_result',
        'settled_odds',
    ];

    protected $casts = [
        'match_timestamp' => 'integer',
        'match_start_time' => 'datetime',
        'odds' => 'decimal:2',
        'settled_odds' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'result_status' => 'pending',
    ];

    // Relationships
    public function slip()
    {
        return $this->belongsTo(BettingSlip::class, 'slip_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function footballMatch()
    {
        return $this->belongsTo(FootballMatch::class, 'match_id', 'id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('result_status', 'pending');
    }

    public function scopeWon($query)
    {
        return $query->where('result_status', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('result_status', 'lost');
    }

    public function scopeVoid($query)
    {
        return $query->where('result_status', 'void');
    }

    public function scopeByMatch($query, $matchId)
    {
        return $query->where('match_id', $matchId);
    }

    // Helper Methods
    public function isWon()
    {
        return $this->result_status === 'won';
    }

    public function isLost()
    {
        return $this->result_status === 'lost';
    }

    public function isPending()
    {
        return $this->result_status === 'pending';
    }

    public function isVoid()
    {
        return $this->result_status === 'void';
    }

    public function markAsWon()
    {
        $this->update(['result_status' => 'won']);
    }

    public function markAsLost()
    {
        $this->update(['result_status' => 'lost']);
    }

    public function markAsVoid()
    {
        $this->update(['result_status' => 'void']);
    }

    // Get bet market label
    public function getBetMarketLabelAttribute()
    {
        $labels = [
            '1x2_home' => 'Home Win (1X2)',
            '1x2_draw' => 'Draw (1X2)',
            '1x2_away' => 'Away Win (1X2)',
            'over' => 'Over',
            'under' => 'Under',
            'odd' => 'Odd',
            'even' => 'Even',
            'handicap_home' => 'Handicap Home',
            'handicap_away' => 'Handicap Away',
            'fh_home' => 'First Half Home',
            'fh_draw' => 'First Half Draw',
            'fh_away' => 'First Half Away',
            'correct_score' => 'Correct Score',
        ];
        return $labels[$this->bet_market] ?? $this->bet_market;
    }

    // Get match title
    public function getMatchTitleAttribute()
    {
        return $this->host_team_name . ' vs ' . $this->guest_team_name;
    }
}