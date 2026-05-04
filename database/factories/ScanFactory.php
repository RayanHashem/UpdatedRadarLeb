<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scan>
 */
class ScanFactory extends Factory
{
    protected $model = Scan::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'game_id'     => Game::factory(),
            'success'     => false,
            'radar_level' => 0,
            'cost'        => 1,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn () => ['success' => true]);
    }

    public function pricedAt(float $cost): static
    {
        return $this->state(fn () => ['cost' => $cost]);
    }
}
