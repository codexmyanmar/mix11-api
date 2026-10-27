<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BettingTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'betting_transactions';

    protected $fillable = [
        'transaction_number',
        'slip_id',
        'slip_number',
        'user_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
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

    // Scopes
    public function scopeBetPlaced($query)
    {
        return $query->where('transaction_type', 'bet_placed');
    }

    public function scopeBetSettled($query)
    {
        return $query->where('transaction_type', 'bet_settled');
    }

    public function scopeRefund($query)
    {
        return $query->where('transaction_type', 'refund');
    }

    // Helper Methods
    public function isBetPlaced()
    {
        return $this->transaction_type === 'bet_placed';
    }

    public function isBetSettled()
    {
        return $this->transaction_type === 'bet_settled';
    }

    public function isRefund()
    {
        return $this->transaction_type === 'refund';
    }

    // Boot method to generate transaction number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->transaction_number)) {
                $model->transaction_number = 'TXN' . date('YmdHis') . rand(1000, 9999);
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }
}