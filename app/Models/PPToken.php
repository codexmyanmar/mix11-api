<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PPToken extends Model
{
    use HasFactory;

    protected $table = 'pp_tokens';
    
    protected $fillable = [
        'token',
        'user_id',
        'game_id',
        'currency',
        'language',
        'ip_address',
        'used',
        'used_at',
        'expires_at'
    ];

    protected $casts = [
        'used' => 'boolean',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('used', false)
                     ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    // Methods
    public function isValid()
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    public function markAsUsed()
    {
        $this->update([
            'used' => true,
            'used_at' => now()
        ]);
    }
}