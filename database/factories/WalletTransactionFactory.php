<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'game_id'       => Game::factory(),
            'scan_id'       => null,
            'type'          => 'debit',
            'amount'        => 1,
            'balance_after' => 0,
            'reference'     => null,
            'notes'         => null,
        ];
    }

    /** Top-up: positive amount, no game, no scan. */
    public function topup(): static
    {
        return $this->state(fn () => [
            'type'    => 'topup',
            'game_id' => null,
            'scan_id' => null,
        ]);
    }
}
