<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuffaloLog extends Model
{
    protected $table = 'buffalo_logs';
    
    protected $fillable = [
        'uid',
        'bet_uid',
        'token',
        'endpoint',
        'request_data',
        'response_data',
        'status'
    ];
    
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array'
    ];
}