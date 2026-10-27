<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferLog extends Model
{
    protected $table = 'refer_logs';

    protected $fillable = [
        'referrer_user_id',
        'referred_user_id',
    ];

    // User who referred
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    // User who was referred
    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
