<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Upsert real admin users for the Filament panel.
     *
     * Passwords are always (re)generated so running this again after a
     * lost-password incident produces fresh credentials instead of silently
     * skipping existing users.  The new passwords are printed to the console
     * — store them securely and change them via the Profile page after login.
     *
     * Run: php artisan db:seed --class=AdminUserSeeder
     */
    public function run(): void
    {
        // Remove legacy fake admin (no longer used)
        User::where('email', 'admin@admin.com')->delete();

        $admins = [
            [
                'name'  => 'Ali Houdeib',
                'email' => 'Ali_houdeib@hotmail.com',
                'role'  => 'super_admin',
            ],
            [
                'name'  => 'Rayan Hashem',
                'email' => 'rayanehashem37@gmail.com',
                'role'  => 'technical_admin',
            ],
        ];

        $rows = [];

        foreach ($admins as $admin) {
            $plainPassword = Str::password(20);
            $hash          = Hash::make($plainPassword);

            // Use a raw DB upsert to avoid the hashed cast double-hashing a
            // pre-hashed value, and to guarantee the password is always reset.
            DB::table('users')->updateOrInsert(
                ['email' => $admin['email']],
                [
                    'name'         => $admin['name'],
                    'role'         => $admin['role'],
                    'password'     => $hash,
                    'phone_number' => null,
                    'game_id'      => null,
                    'updated_at'   => now(),
                    'created_at'   => now(),
                ]
            );

            $rows[] = [$admin['name'], $admin['email'], $admin['role'], $plainPassword];
        }

        if ($this->command) {
            $this->command->newLine();
            $this->command->info('Admin users upserted. Temporary passwords (change after first login via Profile):');
            $this->command->table(
                ['Name', 'Email', 'Role', 'Temporary password'],
                $rows
            );
            $this->command->warn('Store these passwords securely — they will not be shown again.');
            $this->command->newLine();
        }
    }
}
