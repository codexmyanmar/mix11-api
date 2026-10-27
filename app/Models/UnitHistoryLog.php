<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitHistoryLog extends Model
{
    use HasFactory;

    protected $table = 'unit_history_logs';

    protected $fillable = [
        'agent_id',
        'user_id',
        'amount',
        'user_before_amount',
        'user_after_amount',
        'agent_before_amount',
        'agent_after_amount',
        'type',
        'status'
    ];

    protected $casts = [
        'type' => 'integer',
        'amount' => 'integer',
        'user_before_amount' => 'integer',
        'user_after_amount' => 'integer',
        'agent_before_amount' => 'integer',
        'agent_after_amount' => 'integer',
    ];

    /**
     * Get the agent associated with the log.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the user associated with the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}