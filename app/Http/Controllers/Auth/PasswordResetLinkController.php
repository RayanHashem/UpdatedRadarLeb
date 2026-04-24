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
     * Handle an incoming password reset link request.
     *
     * Root-cause hardening (why "forgot password" was not working 100%):
     *  1. Exact-match Rule::exists('users','email') silently failed for users who
     *     signed up with mixed casing (e.g. "Ali_Houdeib@hotmail.com" vs
     *     "ali_houdeib@hotmail.com"). We now resolve the user case-insensitively
     *     and pass the DB-stored email to the broker so the token row is keyed
     *     correctly and the link is built for the right account.
     *  2. MAIL_MAILER=log made sendResetLink() return RESET_LINK_SENT even though
     *     no email ever left the server. We now detect that and fail closed with
     *     a clear instruction, so the UI never lies to the user.
     *  3. SMTP/transport exceptions used to be swallowed into a generic "try
     *     again later". We now log the exception and still return a user-safe
     *     message, but mail failures are actually visible in storage/logs.
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

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'No account found with this email address. Please sign up first.',
            ]);
        }

        if (config('mail.default') === 'log'
            && ! app()->environment('local', 'testing')) {
            Log::warning('Password reset requested while MAIL_MAILER=log in non-local env', [
                'user_id' => $user->id,
            ]);

            return back()->withErrors([
                'email' => 'Email delivery is not configured on the server yet. Please contact support.',
            ]);
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

            return back()->withErrors([
                'email' => 'We could not deliver the reset email right now (mail server error). Please try again shortly.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Unexpected failure sending password reset link', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Unable to send reset email right now. Please try again later.',
            ]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __('A reset link has been sent to your email.'));
        }

        Log::warning('Password::sendResetLink returned non-success status', [
            'user_id' => $user->id,
            'status'  => $status,
        ]);

        return back()->withErrors(['email' => __($status)]);
    }
}
