<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Forget password must not reveal who has an account: every request gets
 * the same reply, and the email only goes to a registered player.
 */
class PasswordResetLinkTest extends TestCase
{
    use RefreshDatabase;

    private const NEUTRAL = 'If an account exists for this email, we’ve sent a password reset link.';

    public function test_the_neutral_reply_is_the_agreed_wording(): void
    {
        $this->assertSame(self::NEUTRAL, PasswordResetLinkController::NEUTRAL_STATUS);
    }

    public function test_registered_player_gets_the_link_and_the_neutral_reply(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'player@example.com']);

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'player@example.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', self::NEUTRAL)
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_email_is_matched_case_insensitively(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'Mixed.Case@Example.com']);

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'mixed.case@example.com'])
            ->assertSessionHas('status', self::NEUTRAL);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_the_same_reply_and_nothing_is_sent(): void
    {
        Notification::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', self::NEUTRAL)
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_admin_email_gets_the_same_reply_and_nothing_is_sent(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'admin@example.com', 'role' => 'admin']);

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'admin@example.com'])
            ->assertSessionHas('status', self::NEUTRAL)
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_a_second_request_too_soon_still_gets_the_same_reply(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'player@example.com']);

        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'player@example.com']);
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'player@example.com'])
            ->assertSessionHas('status', self::NEUTRAL)
            ->assertSessionHasNoErrors();

        // The broker throttles the second request, so only one email went out.
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_a_malformed_email_is_still_a_field_error(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }
}
