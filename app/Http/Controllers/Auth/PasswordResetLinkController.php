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

        Password::sendResetLink(
            $request->only('email')
        );

        return back()->with('status', __('A reset link has been sent to your email.'));
    }
}
