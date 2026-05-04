<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Cache one bcrypt-hashed copy of the default password and reuse it across
     * the whole factory invocation. Hashing is the slowest thing in tests; for
     * the suite we don't need a unique hash per row.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'           => fake()->name(),
            'phone_number'   => fake()->unique()->numerify('7########'),  // Lebanese mobile prefix
            'email'          => fake()->unique()->safeEmail(),
            'date_of_birth'  => fake()->dateTimeBetween('-60 years', '-19 years')->format('Y-m-d'),
            'password'       => static::$password ??= Hash::make('password'),
            'wallet_balance' => 0,
            'role'           => null,                                     // null = public app user
            'remember_token' => Str::random(10),
        ];
    }

    /** Skip email verification — used by tests asserting verification flow. */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    /** Pre-fund a user's wallet. */
    public function withBalance(float $amount): static
    {
        return $this->state(fn () => ['wallet_balance' => $amount]);
    }

    /** Promote to a Filament admin role. Defaults to super_admin. */
    public function admin(string $role = 'super_admin'): static
    {
        return $this->state(fn () => ['role' => $role]);
    }
}
