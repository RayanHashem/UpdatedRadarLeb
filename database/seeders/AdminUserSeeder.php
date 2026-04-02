<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Create real admin users for Filament panel. Removes the legacy fake admin.
     * Run: php artisan db:seed --class=AdminUserSeeder
     * Temporary passwords are echoed at the end — change them after first login.
     */
    public function run(): void
    {
        // Remove legacy fake admin (no longer used)
        User::where('email', 'admin@admin.com')->delete();

        $rows = [];

        // 1. Ali Houdeib — owner / super admin
        $userAli = User::firstOrNew(['email' => 'Ali_houdeib@hotmail.com']);
        $isNewAli = ! $userAli->exists;
        if ($isNewAli) {
            $userAli->password = Hash::make($passwordAli = Str::password(20));
            $rows[] = ['Ali Houdeib', 'Ali_houdeib@hotmail.com', 'super_admin', $passwordAli];
        }
        $userAli->fill([
            'name' => 'Ali Houdeib',
            'role' => 'super_admin',
            'phone_number' => null,
            'game_id' => null,
        ]);
        $userAli->save();

        // 2. Rayan Hashem — technical admin / developer
        $userRayan = User::firstOrNew(['email' => 'rayanehashem37@gmail.com']);
        $isNewRayan = ! $userRayan->exists;
        if ($isNewRayan) {
            $userRayan->password = Hash::make($passwordRayan = Str::password(20));
            $rows[] = ['Rayan Hashem', 'rayanehashem37@gmail.com', 'technical_admin', $passwordRayan];
        }
        $userRayan->fill([
            'name' => 'Rayan Hashem',
            'role' => 'technical_admin',
            'phone_number' => null,
            'game_id' => null,
        ]);
        $userRayan->save();

        if (count($rows) > 0 && $this->command) {
            $this->command->newLine();
            $this->command->info('Admin users created. Temporary passwords (change after first login via Profile):');
            $this->command->table(
                ['Name', 'Email', 'Role', 'Temporary password'],
                $rows
            );
            $this->command->warn('Store these passwords securely.');
            $this->command->newLine();
        }
    }
}
