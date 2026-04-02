<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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
     * Requires the email to belong to an existing registered user.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => [
                'required',
                'email',
                Rule::exists('users', 'email'),
            ],
        ], [
            'email.exists' => 'No account found with this email address. Please sign up first.',
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Password reset email failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Unable to send reset email right now. Please try again later.',
            ]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __('A reset link has been sent to your email.'));
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
