<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingBetCancellation extends Model
{
    protected $fillable = [
        'platform_tx_id',
        'user_id',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}