<?php

namespace App\Http\Controllers\Auth;

use App\Actions\User\RegisterUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Registration controller — kept thin. The actual user-creation orchestration
 * (DOB parsing, age gate, persist + event + login) lives in
 * App\Actions\User\RegisterUser. This file only handles HTTP shape:
 * validation rules + delegate + redirect.
 */
class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly RegisterUser $registerUser,
    ) {
    }

    /** Render the registration page. */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number'         => ['required', 'regex:/^\d{8,15}$/', 'unique:users,phone_number'],
            'password'             => ['required', 'confirmed', Rules\Password::defaults()],
            'date_of_birth'        => ['required', 'string', 'regex:/^\d{1,2}\/\d{1,2}\/\d{4}$/'],
            'confirm_18_and_terms' => ['required', 'accepted'],
        ], [
            'password.required'  => 'Password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        ($this->registerUser)($validated);

        return to_route('dashboard');
    }
}
