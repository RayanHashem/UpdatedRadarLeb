<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = ['name','price','image_path','price_to_play','minimum_amount_for_winning','minimum_deposit','is_enabled','draw_number','target_amount','current_amount'];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
    ];

    public function scans()      { return $this->hasMany(Scan::class); }
    public function stats()      { return $this->hasMany(GameUserStat::class); }
    public function walletTransactions() { return $this->hasMany(WalletTransaction::class); }

    /**
     * Get distinct users who have spent on this game (via wallet transactions).
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

    /** Get the logged-in user’s progress (used by API). */
    public function progressFor(User $user): array
    {
        $stat = $this->stats()->firstOrCreate(['user_id'=>$user->id]);
        return [
            'radar_level'   => $stat->current_radar,
            'failed_scans'  => $stat->failed_scans,
            'successful'    => $stat->successful_scans,
            'amount_spent'  => $stat->amount_spent,
            'can_win_final' => $this->canUserWinFinal($user),
        ];
    }

    /** Build progress array from a pre-loaded stat (avoids N+1 on dashboard). */
    public function progressFromStat(?GameUserStat $stat): array
    {
        return [
            'radar_level'   => $stat->current_radar ?? 0,
            'failed_scans'  => $stat->failed_scans ?? 0,
            'successful'    => $stat->successful_scans ?? 0,
            'amount_spent'  => $stat->amount_spent ?? 0,
            'can_win_final' => false,
        ];
    }

    public function attemptScan(User $user): array
    {
        abort_unless($this->is_enabled == true, 423, 'game_disabled');
        abort_unless(SystemSetting::get('scans_enabled', true), 423, 'Radar offline');

        $cost = (float) $this->price_to_play;

        abort_if((float) $user->wallet_balance < $cost, 402, 'Not enough balance');

        /*
         * Wrap wallet debit + stat update + scan row + wallet transaction in one
         * DB transaction. Previous behaviour: if any step after the wallet
         * decrement threw, the balance was already reduced but the client never
         * got the new `wallet` value back, so the UI displayed the old balance
         * until a full reload — exactly the "I have to reload" bug reported.
         *
         * The RNG that decides win/loss is unchanged — the transaction just
         * guarantees all-or-nothing persistence around it.
         */
        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $cost) {
            $user->refresh();
            abort_if((float) $user->wallet_balance < $cost, 402, 'Not enough balance');

            $stat = $this->stats()->firstOrCreate(['user_id' => $user->id]);

            $user->decrement('wallet_balance', $cost);
            $stat->increment('amount_spent', $cost);
            $this->increment('current_amount', $cost);

            $nextRadar   = min($stat->current_radar + 1, 6);
            $baseFails   = $nextRadar * 10;
            $neededFails = $baseFails + mt_rand(-2, 2);

            $stat->increment('fails_in_level');
            $isSuccess = false;

            if ($stat->fails_in_level >= $neededFails) {
                $isSuccess = (mt_rand(0, 1) === 1);
                if ($isSuccess) {
                    $stat->current_radar     = $nextRadar;
                    $stat->successful_scans += 1;
                    $stat->fails_in_level    = 0;
                }
            }

            if (! $isSuccess) {
                $stat->failed_scans += 1;
            }

            $stat->save();

            $scan = $this->scans()->create([
                'user_id'     => $user->id,
                'success'     => $isSuccess,
                'radar_level' => $stat->current_radar ?? 0,
                'cost'        => $cost,
            ]);

            $user->refresh();

            WalletTransaction::firstOrCreate(
                ['scan_id' => $scan->id],
                [
                    'user_id'       => $user->id,
                    'game_id'       => $this->id,
                    'type'          => 'debit',
                    'amount'        => $cost,
                    'balance_after' => $user->wallet_balance,
                ]
            );

            return [
                'antenna_detected' => $isSuccess,
                'progress'         => $this->progressFor($user),
                'wallet'           => (float) $user->wallet_balance,
            ];
        });
    }

    protected function canUserWinFinal(User $user): bool
    {
        $totalSpent     = $this->stats()->sum('amount_spent');
        if ($totalSpent < $this->minimum_amount_for_winning) { return false; }

        $topIds = $this->stats()
            ->orderByDesc('amount_spent')
            ->take(3)
            ->pluck('user_id')
            ->all();
        return in_array($user->id, $topIds, true);
    }
}
