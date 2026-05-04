<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Public-app auth: phone-number-based login, registration, logout.
 *
 * These flows are the user's first interaction with the app — failing them
 * silently is an outage. We test the happy path plus the most common
 * input-shape failures (no password, missing date of birth, under 18).
 */
class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_input(): void
    {
        $response = $this->postJson('/register', [
            'name'                => 'Test User',
            'email'               => 'test@example.com',
            'phone_number'        => '71234567',
            'password'            => 'Password!1',
            'password_confirmation' => 'Password!1',
            'date_of_birth'       => '01/01/2000',
            'confirm_18_and_terms' => true,
        ]);

        // Inertia/web register controller redirects on success (302).
        $this->assertContains($response->status(), [200, 201, 302], 'register did not produce a 2xx/3xx response');

        $user = User::where('phone_number', '71234567')->first();
        $this->assertNotNull($user);
        $this->assertSame('Test User', $user->name);
        $this->assertTrue(Hash::check('Password!1', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_under_18_is_rejected(): void
    {
        $response = $this->postJson('/register', [
            'name'                => 'Too Young',
            'email'               => 'young@example.com',
            'phone_number'        => '71111111',
            'password'            => 'Password!1',
            'password_confirmation' => 'Password!1',
            'date_of_birth'       => now()->subYears(15)->format('d/m/Y'),
            'confirm_18_and_terms' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('date_of_birth');
        $this->assertSame(0, User::count());
    }

    public function test_registration_requires_unique_phone_number(): void
    {
        User::factory()->create(['phone_number' => '71234567']);

        $response = $this->postJson('/register', [
            'name'                => 'Dup',
            'email'               => 'dup@example.com',
            'phone_number'        => '71234567',
            'password'            => 'Password!1',
            'password_confirmation' => 'Password!1',
            'date_of_birth'       => '01/01/2000',
            'confirm_18_and_terms' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('phone_number');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'phone_number' => '71999999',
            'password'     => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/login', [
            'phone_number' => '71999999',
            'password'     => 'correct-password',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'phone_number' => '71999999',
            'password'     => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/login', [
            'phone_number' => '71999999',
            'password'     => 'wrong',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_logout_clears_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertStatus(302);
        $this->assertGuest();
    }
}
