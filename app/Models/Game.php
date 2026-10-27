<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $table = 'games';

    protected $fillable = [
        'game_name',
        'game_type',
        'game_code',
        'game_provider',
        'game_ggr_fee',
        'game_alias',
        'game_specail',
        'free_spin',
        'order',
        'status',
        'image1',
        'image2',
        'provider_game_id'
    ];

    protected $casts = [
        'free_spin' => 'integer',
        'order' => 'integer',
        'status' => 'integer',
        'game_ggr_fee' => 'string',
    ];
}