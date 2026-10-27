<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BettingSlip extends Model
{
    use HasFactory;

    protected $table = 'betting_slips';

    protected $fillable = [
        'slip_number',
        'user_id',
        'bet_type',
        'bet_category',
        'total_stake',
        'total_odds',
        'potential_win',
        'status',
        'settled_amount',
        'settled_at',
    ];

    protected $casts = [
        'total_stake' => 'decimal:2',
        'total_odds' => 'decimal:2',
        'potential_win' => 'decimal:2',
        'settled_amount' => 'decimal:2',
        'settled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'total_stake' => 0,
        'total_odds' => 0,
        'potential_win' => 0,
        'settled_amount' => 0,
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function selections()
    {
        return $this->hasMany(BettingSelection::class, 'slip_id');
    }

    public function transactions()
    {
        return $this->hasMany(BettingTransaction::class, 'slip_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('bet_category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('bet_type', $type);
    }

    // Helper Methods
    public function isSingle()
    {
        return $this->bet_type === 'single';
    }

    public function isMulti()
    {
        return $this->bet_type === 'multi';
    }

    public function isBody()
    {
        return $this->bet_category === 'body';
    }

    public function isMaung()
    {
        return $this->bet_category === 'maung';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isWon()
    {
        return $this->status === 'won';
    }

    public function isLost()
    {
        return $this->status === 'lost';
    }

    public function calculateTotalOdds()
    {
        $odds = $this->selections()->pluck('odds')->toArray();
        return array_product($odds);
    }

    public function calculatePotentialWin()
    {
        return $this->total_stake * $this->total_odds;
    }

    public function markAsWon()
    {
        $this->update([
            'status' => 'won',
            'settled_amount' => $this->potential_win,
            'settled_at' => now(),
        ]);
    }

    public function markAsLost()
    {
        $this->update([
            'status' => 'lost',
            'settled_amount' => 0,
            'settled_at' => now(),
        ]);
    }

    public function markAsCancelled()
    {
        $this->update([
            'status' => 'cancelled',
            'settled_at' => now(),
        ]);
    }

    // Boot method to generate slip number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slip_number)) {
                $model->slip_number = 'SLP' . date('YmdHis') . rand(1000, 9999);
            }
        });
    }
}