<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JiliGameLog extends Model
{
    protected $table = 'jili_game_logs';

    protected $fillable = [
        'user_id', 'token', 'currency', 'game', 'round',
        'wagers_time', 'bet_amount', 'winlose_amount', 'bet_id',
        'type', 'is_settled', 'is_cancelled',
        'request_raw', 'response_raw'
    ];

    protected $casts = [
        'wagers_time'   => 'datetime',
        'request_raw'   => 'array',
        'response_raw'  => 'array',
        'bet_amount'    => 'decimal:4',
        'winlose_amount'=> 'decimal:4',
        'is_settled'    => 'boolean',
        'is_cancelled'  => 'boolean'
    ];
}