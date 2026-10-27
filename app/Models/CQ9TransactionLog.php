<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CQ9TransactionLog extends Model
{
    protected $table = 'cq9_transaction_logs';
    
    public $timestamps = false;
    
    protected $fillable = [
        'mtcode', 'roundid', 'account', 'action', 'amount',
        'before_balance', 'after_balance', 'currency', 'gamehall',
        'gamecode', 'event_time', 'response_payload', 'request_payload', 'status'
    ];
    
    protected $casts = [
        'amount' => 'decimal:4',
        'before_balance' => 'decimal:4',
        'after_balance' => 'decimal:4',
        'event_time' => 'datetime',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];
}