<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Live22ProcessedTransaction extends Model
{
    use HasFactory;

    protected $table = 'live22_processed_transactions';

    protected $fillable = [
        'transaction_id',
        'type',
        'user_id',
    ];

    /**
     * Constants for type (optional but recommended)
     */
    const TYPE_BET = 'bet';
    const TYPE_RESULT = 'result';
    const TYPE_ROLLBACK = 'rollback';
    const TYPE_BONUS = 'bonus';
    const TYPE_BETNSETTLE = 'betnsettle';

    /**
     * Relationship (optional)
     * Assuming you have User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}