<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Reset the password of a Filament admin user to a value the operator
 * actually knows. The AdminUserSeeder deliberately generates a random
 * 20-char password every time it runs and only prints it once to the
 * console, which means anyone who loses that console output can no longer
 * sign in to /admin/login even though their user row is perfectly valid.
 *
 * Usage:
 *   php artisan admin:set-password rayanehashem37@gmail.com "NewStrongP@ss1"
 *
 * The command refuses to touch app users (role IS NULL) so it can never
 * accidentally hijack a public account — it's strictly for admin accounts.
 */
class SetAdminPassword extends Command
{
    protected $signature = 'admin:set-password
        {email : Email address of the admin user}
        {password : New plaintext password (quote it if it has spaces or symbols)}';

    protected $description = 'Set a known password for a Filament admin user so they can log into /admin/login.';

    public function handle(): int
    {
        $email    = trim((string) $this->argument('email'));
        $password = (string) $this->argument('password');

        if ($email === '' || $password === '') {
            $this->error('Both email and password are required.');
            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        // Case-insensitive lookup so "Rayan@Gmail.com" still finds the row
        // stored as "rayan@gmail.com". Admin-only (role IS NOT NULL) — we
        // never want this command mutating a public app account.
        $user = User::whereNotNull('role')
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        if (! $user) {
            $this->error("No admin user found with email '{$email}'.");
            $this->line('Tip: admin users are the rows in `users` where `role` is not null.');
            return self::FAILURE;
        }

        // Write the hash with a raw update to bypass the `hashed` attribute
        // cast. The cast would re-hash on save if we set $user->password,
        // which is what we want, but a raw update is safer here because it
        // also guarantees we never accidentally touch any other column.
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'password'   => Hash::make($password),
                'updated_at' => now(),
            ]);

        $this->info("Password updated for {$user->email} (role: {$user->role}).");
        $this->line('You can now sign in at /admin/login and change it again from the Profile page.');

        return self::SUCCESS;
    }
}
