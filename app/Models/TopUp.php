<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopUp extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'payment_method', // Stores bank name or method name
        'account_name',    // Account Name
        'account_no',      // Account Number
        'latest_six_number',
        'payemnt_slip',   // Note: typo in your migration 'payemnt_slip', keeping it to match
        'status',         // Assumed 'stastus' typo fixed to 'status'
        'reason',
    ];

    // Status Constants
    const STATUS_PENDING = 0;
    const STATUS_COMPLETE = 1;
    const STATUS_REJECT = 2;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->payemnt_slip 
            ? asset('storage/' . $this->payemnt_slip) 
            : null;
    }
}