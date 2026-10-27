<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rich88UserMapping extends Model
{
    protected $fillable = [
        'user_id',
        'pf_id',
        'api_key'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}