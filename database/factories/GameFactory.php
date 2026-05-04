<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'name'                       => fake()->unique()->words(2, true),
            'price'                      => 1500,
            'image_path'                 => '/assets/imgs/mobile1.png',
            'price_to_play'              => 1,
            'minimum_amount_for_winning' => 1650,
            'minimum_deposit'            => 10,
            'target_amount'              => 1650,
            'current_amount'             => 0,
            'draw_number'                => '1',
            'is_enabled'                 => true,
        ];
    }

    /** Disable the game — used to test the abort_unless($is_enabled) branch. */
    public function disabled(): static
    {
        return $this->state(fn () => ['is_enabled' => false]);
    }

    /** Override the per-scan radar cost. */
    public function pricedAt(float $cost): static
    {
        return $this->state(fn () => ['price_to_play' => $cost]);
    }
}
