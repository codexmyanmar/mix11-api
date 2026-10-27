<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'user_id',
        'user_role',  // Target Audience Role
        'sort',       // Sort Order
        'text',       // Rich Text
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'user_role' => 'integer',
        'sort' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}