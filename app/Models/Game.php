<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Game — one of the five sweepstakes prizes (Mobile, Bike & Electronics, SUV,
 * Muscle Car, Super Cash Prize).
 *
 * Intentionally thin. Business logic lives in App\Actions\Game\*:
 *   - AttemptScan        : run a scan + write Scan + WalletTransaction
 *   - BuildGameProgress  : compose the per-user progress payload for views
 *   - CheckWinEligibility: top-3 + threshold gate for the final draw
 *
 * What stays on the model:
 *   - Mass-assignment allowlist ($fillable)
 *   - Casts
 *   - Eloquent relations
 */
class Game extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * `current_amount` is intentionally excluded — it's the live prize pool,
     * mutated only inside App\Actions\Game\AttemptScan via atomic increment.
     * Allowing mass-assignment would let any future controller code tamper
     * with the pool through a stray `->update($input)` call.
     */
    protected $fillable = [
        'name',
        'price',
        'image_path',
        'price_to_play',
        'minimum_amount_for_winning',
        'minimum_deposit',
        'is_enabled',
        'draw_number',
        'target_amount',
    ];

    protected $casts = [
        'target_amount'  => 'decimal:2',
        'current_amount' => 'decimal:2',
    ];

    public function scans()
    {
        return $this->hasMany(Scan::class);
    }

    public function stats()
    {
        return $this->hasMany(GameUserStat::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Distinct users who have spent on this game (via wallet transactions).
     */
    public function usersWhoSpent()
    {
        return $this->belongsToMany(
            User::class,
            'wallet_transactions',
            'game_id',
            'user_id'
        )
            ->whereIn('wallet_transactions.type', ['debit', 'play', 'spend'])
            ->distinct();
    }
}
