<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBalanceHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'user_balance_history';

    protected $fillable = [
        'user_id',
        'balance_before',
        'balance_after',
        'change_amount',
        'change_type',
        'reference_id',
        'reference_type',
        'description',
        'created_at',
    ];

    protected $casts = [
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Scopes
    public function scopeDeposit($query)
    {
        return $query->where('change_type', 'deposit');
    }

    public function scopeWithdraw($query)
    {
        return $query->where('change_type', 'withdraw');
    }

    public function scopeBetPlaced($query)
    {
        return $query->where('change_type', 'bet_placed');
    }

    public function scopeBetWon($query)
    {
        return $query->where('change_type', 'bet_won');
    }

    public function scopeBetLost($query)
    {
        return $query->where('change_type', 'bet_lost');
    }

    public function scopeRefund($query)
    {
        return $query->where('change_type', 'refund');
    }

    // Helper Methods
    public function isDeposit()
    {
        return $this->change_type === 'deposit';
    }

    public function isWithdraw()
    {
        return $this->change_type === 'withdraw';
    }

    public function isBetPlaced()
    {
        return $this->change_type === 'bet_placed';
    }

    public function isBetWon()
    {
        return $this->change_type === 'bet_won';
    }

    public function isBetLost()
    {
        return $this->change_type === 'bet_lost';
    }

    public function isRefund()
    {
        return $this->change_type === 'refund';
    }

    // Boot method to set created_at
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }
}