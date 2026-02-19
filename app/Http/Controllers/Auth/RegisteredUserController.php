<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => ['required', 'regex:/^\d{8,15}$/', 'unique:users,phone_number'],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
            'date_of_birth' => ['required', 'string', 'regex:/^\d{1,2}\/\d{1,2}\/\d{4}$/'],
            'confirm_18_and_terms' => ['required', 'accepted'],
        ], [
            'password.required' => 'Password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $dob = $this->parseDateOfBirth($request->date_of_birth);
        if ($dob === null) {
            throw ValidationException::withMessages([
                'date_of_birth' => ['Please enter a valid date (DD/MM/YYYY).'],
            ]);
        }
        if ($dob->age < 18) {
            throw ValidationException::withMessages([
                'date_of_birth' => ['You must be at least 18 years old.'],
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'date_of_birth' => $dob->format('Y-m-d'),
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }

    /**
     * Parse DD/MM/YYYY string to Carbon instance. Returns null if invalid.
     */
    private function parseDateOfBirth(string $value): ?Carbon
    {
        $normalized = preg_replace('/\s+/', '', trim($value));
        $carbon = Carbon::createFromFormat('d/m/Y', $normalized);
        if (! $carbon instanceof Carbon) {
            return null;
        }
        return $carbon;
    }
}
