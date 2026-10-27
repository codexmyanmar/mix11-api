<?php
// app/Models/JokerTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JokerTransaction extends Model
{
    use HasFactory;

    protected $table = 'joker_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'old_balance',
        'new_balance',
        'reference_id',
        'round_id',
        'game_code',
        'description',
        'provider',
        'metadata',
        'request_hash',
        'ip_address'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'old_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}