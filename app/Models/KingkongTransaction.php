<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KingkongTransaction extends Model
{
    protected $fillable = [
        'user_id', 'bet_id', 'settle_id', 'round_id', 
        'game_code', 'bet_amount', 'win_amount', 'status'
    ];
}