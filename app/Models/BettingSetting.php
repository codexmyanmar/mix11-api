<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BettingSetting extends Model
{
    use HasFactory;

    protected $table = 'betting_settings';

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    protected $casts = [
        'setting_value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Get setting value by key
    public static function getValue($key, $default = null)
    {
        $setting = self::where('setting_key', $key)->first();
        return $setting ? $setting->setting_value : $default;
    }

    // Set setting value
    public static function setValue($key, $value)
    {
        return self::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );
    }

    // Get minimum bet amount
    public static function getMinBetAmount()
    {
        return self::getValue('min_bet_amount', 1000);
    }

    // Get maximum bet amount
    public static function getMaxBetAmount()
    {
        return self::getValue('max_bet_amount', 1000000);
    }

    // Get maximum multiple bets
    public static function getMaxMultipleBets()
    {
        return (int) self::getValue('max_multiple_bets', 10);
    }

    // Get minimum multiple bets
    public static function getMinMultipleBets()
    {
        return (int) self::getValue('min_multiple_bets', 2);
    }

    // Get maximum payout
    public static function getMaxPayout()
    {
        return self::getValue('max_payout', 50000000);
    }
}