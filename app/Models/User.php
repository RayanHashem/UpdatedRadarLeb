<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\WalletTransaction;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /** Roles that can access the Filament admin panel. Null role = app user only. */
    public const ADMIN_ROLES = ['super_admin', 'technical_admin', 'admin', 'staff'];

    /**
     * Mass-assignable attributes.
     *
     * Explicit allowlist instead of `$guarded = []` so that future
     * `$user->update($request->validated())` calls cannot accidentally set
     * `wallet_balance`, `role`, or `email_verified_at`. Money is mutated
     * only through dedicated paths (App\Actions\Game\AttemptScan / Filament topoff
     * actions); the admin role is set only by seeders.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'password',
        'date_of_birth',
        'game_id',
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
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role !== null && in_array($this->role, self::ADMIN_ROLES, true);
    }

    /** Exclude admin users (users with a role) from app user lists/reports. */
    public function scopeExcludeAdmins($query)
    {
        return $query->whereNull('role');
    }

    /** Whether this user is an admin (has any admin role). */
    public function isAdmin(): bool
    {
        return $this->role !== null && in_array($this->role, self::ADMIN_ROLES, true);
    }

    /** Subquery/sum for radar cash spent (sum of debit transactions). Use with list queries to avoid N+1. */
    public function scopeWithRadarCashSpent($query)
    {
        return $query->withSum(['walletTransactions as radar_cash_spent' => fn ($q) => $q->where('type', 'debit')], 'amount');
    }

    /** Subquery for count of distinct games a user has spent on. Avoids N+1 in table description callbacks. */
    public function scopeWithDistinctGameCount($query)
    {
        return $query->addSelect([
            'distinct_game_count' => WalletTransaction::selectRaw('COUNT(DISTINCT game_id)')
                ->whereColumn('user_id', 'users.id')
                ->whereNotNull('game_id')
                ->whereIn('type', ['debit', 'play', 'spend']),
        ]);
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
