<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Change password (/settings/password) must enforce the same rules as
 * Sign up: 8+ characters, an uppercase and a lowercase letter, a number
 * and a symbol.
 */
class SettingsPasswordTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Ab1!xyz'],
            'no uppercase' => ['newpass2026!'],
            'no lowercase' => ['NEWPASS2026!'],
            'no number' => ['NewPassword!'],
            'no symbol' => ['NewPass2026'],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function test_weak_new_passwords_are_refused(string $weak): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/settings/password', [
                'old_password' => 'password',
                'new_password' => $weak,
                'new_password_confirmation' => $weak,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('new_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_a_strong_new_password_is_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/settings/password', [
                'old_password' => 'password',
                'new_password' => 'NewPass2026!',
                'new_password_confirmation' => 'NewPass2026!',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('NewPass2026!', $user->fresh()->password));
    }

    public function test_the_current_password_must_still_be_right(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/settings/password', [
                'old_password' => 'not-my-password',
                'new_password' => 'NewPass2026!',
                'new_password_confirmation' => 'NewPass2026!',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('old_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
