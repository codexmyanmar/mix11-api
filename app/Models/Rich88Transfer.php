<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rich88Transfer extends Model
{
    protected $fillable = [
        'user_id',
        'transfer_no',
        'transfer_type',
        'amount',
        'status',
        'balance_before',
        'balance_after',
        'currency',
        'round_id',
        'game_code'
    ];

    
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}