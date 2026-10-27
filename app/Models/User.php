<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; 
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens; // <--- 1. Import this

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles; // <--- 2. Add HasApiTokens here

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        "status",
        "commission",
        "balance",
        "withdraw_able",
        "user_id",     
        "last_login_ip", 
        'wallet_locked',
        'kingkong_token',
        'pp_token',
        'rich88_account',
         'rich88_session_id',
    'rich88_session_expires_at',
    'rich88_pf_id',
    "refer_code"
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // 1. Admin Panel (admin.localhost)
        if ($panel->getId() === 'admin') {
            return $this->id === 1 || $this->hasRole('admin');
        }

        // 2. Super Panel (super.localhost)
        if ($panel->getId() === 'super') {
            return $this->hasRole('super');
        }

        // 2. Super Panel (super.localhost)
        if ($panel->getId() === 'senior') {
            return $this->hasRole('senior');
        }

        // 3. Master Panel (master.localhost)
        if ($panel->getId() === 'master') {
            return $this->hasRole('master');
        }

        // 4. Agent Panel (agent.localhost)
        if ($panel->getId() === 'agent') {
            return $this->hasRole('agent');
        }

        // 5. User Panel (localhost - main domain)
        if ($panel->getId() === 'user') {
            return $this->hasRole('user');
        }

        return false; // Block access to any unknown panel
    }

    // referrals this user made
    public function referralsMade()
    {
        return $this->hasMany(ReferLog::class, 'referrer_user_id');
    }

    // who referred this user
    public function referredBy()
    {
        return $this->hasOne(ReferLog::class, 'referred_user_id');
    }

    public function balanceTransactions()
    {
        return $this->hasMany(UserBalanceTransaction::class, 'user_id');
    }
    
    // Add method to generate session
public function generateRich88Session()
{
    $this->rich88_session_id = bin2hex(random_bytes(32));
    $this->rich88_session_expires_at = now()->addHours(8);
    $this->save();
    return $this->rich88_session_id;
}

// Add method to validate session
public function validateRich88Session($sessionId)
{
    return $this->rich88_session_id === $sessionId && 
           $this->rich88_session_expires_at > now();
}

// Add these methods to your User model

// Betting relationships
public function bettingSlips()
{
    return $this->hasMany(BettingSlip::class, 'user_id', 'id');
}

public function bettingSelections()
{
    return $this->hasMany(BettingSelection::class, 'user_id', 'id');
}

public function bettingTransactions()
{
    return $this->hasMany(BettingTransaction::class, 'user_id', 'id');
}

public function balanceHistories()
{
    return $this->hasMany(UserBalanceHistory::class, 'user_id', 'id');
}

// Betting helper methods
public function getPendingBets()
{
    return $this->bettingSlips()->where('status', 'pending')->get();
}

public function getTotalStaked()
{
    return $this->bettingSlips()->sum('total_stake');
}

public function getTotalWon()
{
    return $this->bettingSlips()->where('status', 'won')->sum('settled_amount');
}

public function getTotalLost()
{
    return $this->bettingSlips()->where('status', 'lost')->sum('total_stake');
}

public function canPlaceBet($amount)
{
    $minBet = BettingSetting::getMinBetAmount();
    $maxBet = BettingSetting::getMaxBetAmount();
    
    return $amount >= $minBet && 
           $amount <= $maxBet && 
           $this->balance >= $amount &&
           !$this->wallet_locked;
}

public function deductBalance($amount, $referenceId = null, $description = null)
{
    if ($this->balance < $amount) {
        return false;
    }
    
    $balanceBefore = $this->balance;
    $this->balance -= $amount;
    $this->save();
    
    UserBalanceHistory::create([
        'user_id' => $this->id,
        'balance_before' => $balanceBefore,
        'balance_after' => $this->balance,
        'change_amount' => -$amount,
        'change_type' => 'bet_placed',
        'reference_id' => $referenceId,
        'reference_type' => BettingSlip::class,
        'description' => $description,
    ]);
    
    return true;
}

public function addBalance($amount, $changeType, $referenceId = null, $referenceType = null, $description = null)
{
    $balanceBefore = $this->balance;
    $this->balance += $amount;
    $this->save();
    
    UserBalanceHistory::create([
        'user_id' => $this->id,
        'balance_before' => $balanceBefore,
        'balance_after' => $this->balance,
        'change_amount' => $amount,
        'change_type' => $changeType,
        'reference_id' => $referenceId,
        'reference_type' => $referenceType,
        'description' => $description,
    ]);
    
    return true;
}

}