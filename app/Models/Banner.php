<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    protected $fillable = [
        'user_id',
        'sort',
        'user_role',
        'image',
        'status',
         "link",
        "category"
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort' => 'integer',
        'user_role' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getImageUrlAttribute()
    {
        // return $this->image 
        //     ? asset('storage/' . $this->image) 
        //     : null;
         return $this->image ;
    }
}