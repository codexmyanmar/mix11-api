<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; 

class Withdraw extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'payment_method', 
        'account_name',   
        'account_no',     
        'status',         
        'reason',
    ];

    // Status Constants
    const STATUS_PENDING = 0;
    const STATUS_COMPLETE = 1;
    const STATUS_REJECT = 2;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}