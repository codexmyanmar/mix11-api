<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactChannel extends Model
{
    use HasFactory;

    // Table name (optional, Laravel can guess)
    protected $table = 'contact_channels';

    // Fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'sort',
        'user_role',
        'title',
        'link',
        'icon',
        'status',
    ];

    // Casts for automatic type conversion
    protected $casts = [
        'sort' => 'integer',
        'user_role' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Relationship: ContactChannel belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: only active channels
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: order channels by 'sort' column
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort', 'asc');
    }

    /**
     * Optional: Accessor to get icon URL if stored as path
     */
    public function getIconUrlAttribute()
    {
        if ($this->icon) {
            return asset('storage/' . $this->icon);
        }
        return null;
    }
}
