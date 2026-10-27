<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuffaloTransaction extends Model
{
    protected $table = 'buffalo_transactions';
    
    protected $fillable = [
        'uid',
        'bet_uid',
        'token',
        'changemoney',
        'bet',
        'win',
        'room_id',
        'game_id',
        'status',
        'raw_request'
    ];
    
    protected $casts = [
        'changemoney' => 'integer',
        'bet' => 'integer',
        'win' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}