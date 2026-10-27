<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PPTransaction extends Model
{
    use HasFactory;

    protected $table = 'pp_transactions';
    
    protected $fillable = [
        'reference',
        'transaction_id',
        'user_id',
        'type',
        'amount',
        'old_balance',
        'new_balance',
        'currency',
        'game_id',
        'round_id',
        'metadata',
        'ip_address',
        'user_agent',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'old_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'metadata' => 'array',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function round()
    {
        return $this->belongsTo(PPRound::class, 'round_id', 'round_id');
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRound($query, $roundId)
    {
        return $query->where('round_id', $roundId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getTypeLabelAttribute()
    {
        $labels = [
            'bet' => 'Bet',
            'win' => 'Win',
            'refund' => 'Refund',
            'bonus_win' => 'Bonus Win',
            'jackpot_win' => 'Jackpot Win',
            'adjustment' => 'Adjustment',
            'promo_win' => 'Promo Win'
        ];
        
        return $labels[$this->type] ?? ucfirst($this->type);
    }
}