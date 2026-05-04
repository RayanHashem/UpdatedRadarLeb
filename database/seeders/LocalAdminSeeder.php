<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Local-only seeder that creates a predictable dev admin for the Filament
 * panel and a public test user. Refuses to run outside `APP_ENV=local`
 * because the credentials are intentionally weak (designed for fast iteration,
 * not security).
 *
 * Run:  php artisan db:seed --class=LocalAdminSeeder
 *
 * Credentials it creates:
 *   - Public test user → phone 12341234 / password "password"
 *   - Dev admin       → email dev@radar.local / password "devadmin" / role super_admin
 *
 * Both records use updateOrInsert so re-running the seeder just resets the
 * passwords (handy when you've forgotten them).
 */
class LocalAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->error('LocalAdminSeeder is local-only. Refusing to run in '.app()->environment().'.');

            return;
        }

        $now = now();

        // Public test user
        DB::table('users')->updateOrInsert(
            ['phone_number' => '12341234'],
            [
                'name'         => 'Test User',
                'email'        => null,
                'role'         => null,
                'password'     => Hash::make('password'),
                'updated_at'   => $now,
                'created_at'   => $now,
            ]
        );

        // Dev admin (Filament)
        DB::table('users')->updateOrInsert(
            ['email' => 'dev@radar.local'],
            [
                'name'         => 'Dev Admin',
                'phone_number' => null,
                'role'         => 'super_admin',
                'password'     => Hash::make('devadmin'),
                'updated_at'   => $now,
                'created_at'   => $now,
            ]
        );

        $this->command?->newLine();
        $this->command?->info('Local credentials seeded:');
        $this->command?->table(
            ['Audience',          'Identifier',           'Password'],
            [
                ['Public app',    'phone 12341234',       'password'],
                ['Filament admin','dev@radar.local',      'devadmin'],
            ]
        );
        $this->command?->warn('These are LOCAL DEV credentials. Never use them outside APP_ENV=local.');
        $this->command?->newLine();
    }
}
