<?php
// app/Models/AwcPlayer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwcPlayer extends Model
{
    protected $table = 'awc_players';
    
    protected $fillable = [
        'user_id',
        'awc_user_id',
        'currency',
        'language',
        'user_name',
        'status',
        'bet_limit',
        'is_jackpot_enabled',
        'awc_response',
        'created_at',
        'updated_at'
    ];
    
    protected $casts = [
        'bet_limit' => 'array',
        'awc_response' => 'array',
        'is_jackpot_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Get the user that owns the AWC player
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scope for active players
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    /**
     * Check if player is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}