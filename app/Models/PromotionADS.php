<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionADS extends Model
{
    use HasFactory;

    // Table name (optional, Laravel will guess from model name)
    protected $table = 'promotion_a_d_s';

    // Fillable fields (for mass assignment)
    protected $fillable = [
        'user_id',
        'sort',
        'user_role',
        'title',
        'image',
        'status',
        'description',
    ];

    // Casts
    protected $casts = [
        'sort' => 'integer',
        'user_role' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Relationship: Promotion belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    protected $appends = ['image_url'];

    /**
     * Scope to order promotions by 'sort' column
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort', 'asc');
    }

    public function getImageUrlAttribute()
    {
        return $this->image 
            ? asset('storage/' . $this->image) 
            : null;
    }
}
