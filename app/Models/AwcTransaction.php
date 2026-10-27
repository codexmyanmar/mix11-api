<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwcTransaction extends Model
{
    protected $table = 'awc_transactions';
    
    protected $fillable = [
        // Core identifiers
        'platform_tx_id',
        'settlement_platform_tx_id',
        'ref_platform_tx_id',
        'user_id',
        'awc_user_id',
        
        // Game info
        'platform',
        'game_type',
        'game_code',
        'game_name',
        'bet_type',
        'round_id',
        
        // Amounts
        'bet_amount',
        'win_amount',
        'jackpot_bet_amount',
        'jackpot_win_amount',
        'turnover',
        
        // Balance tracking (CRITICAL)
        'original_balance',
        'balance_after',
        
        // Status
        'status',
        'void_type',
        'type',
        
        // Timestamps
        'bet_time',
        'tx_time',
        'update_time',
        'settle_time',
        'cancel_time',
        'void_time',
        
        // Additional data
        'game_info',
        'tip_info',
        
        // Metadata
        'is_premium',
        'currency',
    ];
    
    protected $casts = [
        'game_info' => 'array',
        'tip_info' => 'array',
        'bet_amount' => 'decimal:4',
        'win_amount' => 'decimal:4',
        'jackpot_bet_amount' => 'decimal:4',
        'jackpot_win_amount' => 'decimal:4',
        'turnover' => 'decimal:4',
        'original_balance' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'is_premium' => 'boolean',
        'bet_time' => 'datetime',
        'tx_time' => 'datetime',
        'update_time' => 'datetime',
        'settle_time' => 'datetime',
        'cancel_time' => 'datetime',
        'void_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Scopes
    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }
    
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    public function scopeBetNSettleTransactions($query)
    {
        return $query->whereNotNull('original_balance')->where('status', 'settle');
    }
    
    // Helper methods
    public function isBetNSettle()
    {
        return !is_null($this->original_balance);
    }
    
    public function getNetProfitAttribute()
    {
        return $this->win_amount - $this->bet_amount;
    }
}