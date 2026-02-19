<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the admin user for Filament panel access.
     * User passes canAccessPanel() because email ends with @admin.com.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('admin123'),
                'phone_number' => 'admin', // required: session uses getAuthIdentifierName() => phone_number
                'game_id' => null, // no prize (do not use Main Game fallback)
            ]
        );
    }
}
