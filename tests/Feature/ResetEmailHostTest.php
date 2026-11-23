<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A forged Host / X-Forwarded-Host header must not get into the password
 * reset email. Outside local, PortBasedSessionIsolation leaves app.url at
 * APP_URL, so neither the email's header link (Laravel's mail layout links
 * to app.url) nor the reset link (which falls back to app.url when
 * MAIL_RESET_BASE_URL isn't set) can point at the forged host.
 */
class ResetEmailHostTest extends TestCase
{
    use RefreshDatabase;

    private const FORGED = 'evil.example';

    /**
     * The test client takes the Host from the request URL (a Host header is
     * ignored), so a forged Host is sent as the URL's host.
     *
     * @return array<string, array{string, array<string, string>}>
     */
    public static function forgedRequests(): array
    {
        return [
            'forged Host' => ['http://'.self::FORGED.'/forgot-password', []],
            'forged X-Forwarded-Host' => ['/forgot-password', ['X-Forwarded-Host' => self::FORGED]],
        ];
    }

    /** @param array<string, string> $headers */
    #[DataProvider('forgedRequests')]
    public function test_a_forged_host_does_not_reach_the_reset_email_outside_local(string $url, array $headers): void
    {
        $this->assertFalse(app()->environment('local'));
        Notification::fake();
        config([
            'app.url' => 'https://radarleb.example',
            'app.reset_base_url' => null, // worst case: the reset link falls back to app.url
        ]);
        $user = User::factory()->create(['email' => 'player@example.com']);

        $this->withHeaders($headers)
            ->from('/forgot-password')
            ->post($url, ['email' => 'player@example.com']);

        $this->assertSame('https://radarleb.example', config('app.url'));

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);
            $html = (string) $mail->render();

            $this->assertStringStartsWith('https://radarleb.example/reset-password/', $mail->actionUrl);
            $this->assertStringContainsString('href="https://radarleb.example"', $html); // header link
            $this->assertStringNotContainsString(self::FORGED, $mail->actionUrl);
            $this->assertStringNotContainsString(self::FORGED, $html);

            return true;
        });
    }

    public function test_local_still_follows_the_request_host_for_the_8000_and_8001_setup(): void
    {
        $this->app['env'] = 'local';
        config(['app.url' => 'http://localhost:8000']);

        $this->get('http://localhost:8001/login');

        $this->assertSame('http://localhost:8001', config('app.url'));
    }
}
