<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the password reset link request page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * The one reply every request gets, so the page never reveals whether an
     * email address has an account.
     */
    public const NEUTRAL_STATUS = 'If an account exists for this email, we’ve sent a password reset link.';

    /**
     * Handle an incoming password reset link request.
     *
     * Always answers with NEUTRAL_STATUS (registered or not, sent or not), so
     * the form can't be used to find out who has an account. The email only
     * goes out when the address belongs to a player. Anything that stops it
     * (unknown address, mail not configured, SMTP failure, throttled) is
     * written to the log instead of being shown.
     *
     * Earlier hardening kept:
     *  1. The user is resolved case-insensitively and the DB-stored email is
     *     passed to the broker, so mixed-case sign-ups still get their link.
     *  2. MAIL_MAILER=log outside local/testing is detected and logged (no
     *     email would leave the server).
     *  3. SMTP/transport exceptions are logged with the mailer and host.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ]);

        $normalizedEmail = strtolower(trim($data['email']));

        /*
         * Scope to app users only. The `users` table also holds admin rows
         * (role != null) that log in via /admin, and a legacy dataset has two
         * rows that differ only in the letter case of the email. Before this
         * scope the case-insensitive match could return the admin row and
         * mint a reset token the real player would never receive.
         */
        $user = User::query()
            ->whereNull('role')
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if ($user) {
            $this->sendLink($user);
        }

        return back()->with('status', self::NEUTRAL_STATUS);
    }

    /**
     * Send the reset email to a known player. Failures are logged, never shown.
     */
    private function sendLink(User $user): void
    {
        if (config('mail.default') === 'log'
            && ! app()->environment('local', 'testing')) {
            Log::warning('Password reset requested while MAIL_MAILER=log in non-local env', [
                'user_id' => $user->id,
            ]);

            return;
        }

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (TransportExceptionInterface $e) {
            Log::error('SMTP transport failed sending password reset link', [
                'user_id' => $user->id,
                'mailer'  => config('mail.default'),
                'host'    => config('mail.mailers.smtp.host'),
                'error'   => $e->getMessage(),
            ]);

            return;
        } catch (\Throwable $e) {
            Log::error('Unexpected failure sending password reset link', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return;
        }

        if ($status !== Password::RESET_LINK_SENT) {
            Log::warning('Password::sendResetLink returned non-success status', [
                'user_id' => $user->id,
                'status'  => $status,
            ]);
        }
    }
}
