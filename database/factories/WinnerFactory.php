<?php

namespace Database\Factories;

use App\Models\Winner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Winner>
 */
class WinnerFactory extends Factory
{
    protected $model = Winner::class;

    public function definition(): array
    {
        return [
            'game_name' => fake()->randomElement(['Mobile', 'Bike & Electronics', 'SUV', 'Muscle Car', 'Super Cash Prize']),
            'user_name' => fake()->name(),
        ];
    }
}
