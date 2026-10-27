<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBalanceTransaction extends Model
{
    // Table name
    protected $table = 'user_balance_transactions';

    // Mass assignable attributes
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'reason',
        'status',
        'comment',
        
        // --- Game Sync Fields (Must match Migration exactly) ---
        'remote_id',
        'provider_code',
        'game_code',
        'game_name',
        'stake_amount',
        'win_loss_amount',
        'is_processed',
        'is_approved',
        'meta_data',
        'approval_data',
        "which_type"
    ];

    // Type casting
    protected $casts = [
        'user_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        
        // Money should be decimal/float to handle cents
        'amount' => 'decimal:2',
        'stake_amount' => 'decimal:2',
        'win_loss_amount' => 'decimal:2',
        
        'is_processed' => 'boolean',
        'is_approved' => 'boolean',
        
        // Auto-handle JSON serialization
        'meta_data' => 'array',
        'approval_data' => 'array',
    ];

    // Constants
    public const TYPE_ADD    = 1;
    public const TYPE_REDUCE = 2;
    public const TYPE_FREEZE = 3;
    public const TYPE_GAME_SYNC = 4; // Used for "Each Match" logs

    public const STATUS_PENDING  = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_FAILED    = 3;

    /**
     * Relation: Transaction belongs to a User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Check if transaction is approved/completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get human-readable type
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_ADD => 'Add',
            self::TYPE_REDUCE => 'Reduce',
            self::TYPE_FREEZE => 'Freeze',
            default => 'Unknown',
        };
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            default => 'Unknown',
        };
    }
}
