<?php

namespace App\Actions\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Register a new public-facing user, log them in, fire the Registered event.
 *
 * Lives in an action class because:
 *   - The DOB parsing + age-gate is non-trivial logic that belongs out of
 *     the controller. Form Requests handle field-level validation; the
 *     "must be 18+" rule is a domain rule, not a request rule.
 *   - Future flows that create a user (admin invite, social login, etc.)
 *     can reuse the same User::create + Auth::login + Registered event
 *     sequence.
 *
 * Inputs are passed as a plain array — the calling controller has already
 * run them through a Form Request, so they're validated. We trust the keys.
 */
class RegisterUser
{
    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     phone_number: string,
     *     password: string,
     *     date_of_birth: string,
     * } $attributes  Validated input from the registration form.
     */
    public function __invoke(array $attributes): User
    {
        $dob = $this->parseDateOfBirth($attributes['date_of_birth']);

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
            'name'          => $attributes['name'],
            'email'         => $attributes['email'],
            'phone_number'  => $attributes['phone_number'],
            'date_of_birth' => $dob->format('Y-m-d'),
            'password'      => Hash::make($attributes['password']),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return $user;
    }

    /** Parse the DD/MM/YYYY string the front-end submits; null if invalid. */
    private function parseDateOfBirth(string $value): ?Carbon
    {
        $normalized = preg_replace('/\s+/', '', trim($value));
        $carbon     = Carbon::createFromFormat('d/m/Y', (string) $normalized);

        return $carbon instanceof Carbon ? $carbon : null;
    }
}
