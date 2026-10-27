<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PPGame extends Model
{
    use HasFactory;

    protected $table = 'pp_games';
    
    protected $fillable = [
        'game_id',
        'game_id_numeric',
        'game_name',
        'game_type_id',
        'type_description',
        'technology',
        'technology_id',
        'platform',
        'demo_game_available',
        'aspect_ratio',
        'jurisdictions',
        'frb_available',
        'fc_available',
        'variable_frb_available',
        'lines',
        'features',
        'data_type',
        'studio',
        'icon_url',
        'is_active'
    ];

    protected $casts = [
        'jurisdictions' => 'array',
        'features' => 'array',
        'demo_game_available' => 'boolean',
        'frb_available' => 'boolean',
        'fc_available' => 'boolean',
        'variable_frb_available' => 'boolean',
        'is_active' => 'boolean',
        'game_id_numeric' => 'integer',
        'lines' => 'integer'
    ];

    // Relationships
    public function rounds()
    {
        return $this->hasMany(PPRound::class, 'game_id', 'game_id');
    }

    public function transactions()
    {
        return $this->hasMany(PPTransaction::class, 'game_id', 'game_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStudio($query, $studio)
    {
        return $query->where('studio', $studio);
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('game_type_id', $typeId);
    }

    public function scopeSlots($query)
    {
        return $query->where('game_type_id', 'vs');
    }

    public function scopeHasFeature($query, $feature)
    {
        return $query->whereJsonContains('features', $feature);
    }

    // Accessors
    public function getIconUrlAttribute($value)
    {
        if ($value) {
            return $value;
        }
        
        // Default icon URL pattern from PP docs
        return "https://api.prerelease-env.biz/game_pic/rec/339/{$this->game_id}.png";
    }

    public function getSquareIconAttribute()
    {
        return "https://api.prerelease-env.biz/game_pic/square/200/{$this->game_id}.png";
    }

    public function getFeatureListAttribute()
    {
        $features = [
            'BUY' => 'Buy Feature',
            'ANTE' => 'Ante Bet',
            'SUPER_SPIN' => 'Super Spin',
            'FREE_BONUS_FEATURE' => 'Free Bonus',
            'TUMBLING' => 'Tumbling Reels',
            'PAY_ANYWHERE' => 'Pay Anywhere',
            'CLUSTER' => 'Cluster Pays',
            'MEGAWAYS' => 'Megaways',
            'PJACKPOT' => 'Progressive Jackpot',
            'ARCADE' => 'Arcade'
        ];
        
        $list = [];
        foreach (($this->features ?? []) as $feature) {
            $list[] = $features[$feature] ?? $feature;
        }
        
        return $list;
    }
}