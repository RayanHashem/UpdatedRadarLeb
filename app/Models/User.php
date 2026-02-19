<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

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
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@admin.com');
    }

    /** Exclude admin users (email ends with @admin.com) from lists/reports. */
    public function scopeExcludeAdmins($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('email')->orWhere('email', 'not like', '%@admin.com');
        });
    }

    /** Subquery/sum for radar cash spent (sum of debit transactions). Use with list queries to avoid N+1. */
    public function scopeWithRadarCashSpent($query)
    {
        return $query->withSum(['walletTransactions as radar_cash_spent' => fn ($q) => $q->where('type', 'debit')], 'amount');
    }

    public function game() { return $this->belongsTo(Game::class); }
    public function scans()  { return $this->hasMany(Scan::class); }
    public function gameStats() { return $this->hasMany(GameUserStat::class); }
    public function walletTransactions() { return $this->hasMany(WalletTransaction::class); }
    public function devices() { return $this->hasMany(Device::class); }
    public function sessions() { return $this->hasMany(UserSession::class); }

    /**
     * Get distinct games the user has spent on (via wallet transactions with game_id).
     */
    public function gamesSpentOn()
    {
        return $this->belongsToMany(
            Game::class,
            'wallet_transactions',
            'user_id',
            'game_id'
        )
        ->whereNotNull('wallet_transactions.game_id')
        ->whereIn('wallet_transactions.type', ['debit', 'play', 'spend'])
        ->distinct();
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'phone_number';
    }
}
