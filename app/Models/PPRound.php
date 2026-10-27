<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PPRound extends Model
{
    use HasFactory;

    protected $table = 'pp_rounds';
    
    protected $fillable = [
        'round_id',
        'user_id',
        'game_id',
        'game_name',
        'currency',
        'total_bet',
        'total_win',
        'net_result',
        'start_balance',
        'end_balance',
        'status',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'total_bet' => 'decimal:2',
        'total_win' => 'decimal:2',
        'net_result' => 'decimal:2',
        'start_balance' => 'decimal:2',
        'end_balance' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(PPTransaction::class, 'round_id', 'round_id');
    }

    public function game()
    {
        return $this->belongsTo(PPGame::class, 'game_id', 'game_id');
    }

    // Scopes
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors
    public function getDurationAttribute()
    {
        if ($this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at) . ' seconds';
        }
        return 'In progress';
    }

    public function getIsCompleteAttribute()
    {
        return $this->status === 'completed';
    }
}