<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Draw;
use Illuminate\Database\Seeder;

class GamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // target_amount = retail + 10% (marketing). price_to_play = Radar Cash units per scan (not $/radar × radars).
        $games = [
            [
                'name' => 'Mobile',
                'price' => 1650.00,
                'target_amount' => 1650.00,   // base 1500 +10%
                'price_to_play' => 1.00,      // 1 scan = 1 radar
                'minimum_deposit' => 10.00,
                'minimum_amount_for_winning' => 1650,
            ],
            [
                'name' => 'Bike & Electronics',
                'price' => 16500.00,
                'target_amount' => 16500.00,  // base 15000 +10%
                'price_to_play' => 4.00,      // 1 scan = 4 radars
                'minimum_deposit' => 25.00,
                'minimum_amount_for_winning' => 16500,
            ],
            [
                'name' => 'SUV',
                'price' => 55000.00,
                'target_amount' => 55000.00,  // base 50000 +10%
                'price_to_play' => 8.00,      // 1 scan = 8 radars
                'minimum_deposit' => 30.00,
                'minimum_amount_for_winning' => 55000,
            ],
            [
                'name' => 'Muscle Car',
                'price' => 165000.00,
                'target_amount' => 165000.00, // base 150000 +10%
                'price_to_play' => 24.00,     // 1 scan = 24 radars
                'minimum_deposit' => 40.00,
                'minimum_amount_for_winning' => 165000,
            ],
            [
                'name' => 'Super Cash Prize',
                'price' => 220000.00,
                'target_amount' => 220000.00, // base 200000 +10%
                'price_to_play' => 32.00,     // 1 scan = 32 radars
                'minimum_deposit' => 50.00,
                'minimum_amount_for_winning' => 220000,
            ],
        ];

        foreach ($games as $gameData) {
            // Upsert game by name
            $game = Game::updateOrCreate(
                ['name' => $gameData['name']],
                array_merge($gameData, [
                    'draw_number' => '1',
                    'is_enabled' => true,
                    ])
            );

            $this->command->info("Game '{$game->name}' created/updated with ID={$game->id}");

            // Ensure an initial open draw exists for this game
            $existingDraw = Draw::where('game_id', $game->id)
                ->where('draw_number', 1)
                ->first();

            if (!$existingDraw) {
                Draw::create([
                    'game_id' => $game->id,
                    'draw_number' => 1,
                    'status' => 'open',
                    'opened_at' => now(),
                ]);
                $this->command->info("  -> Draw #1 created for '{$game->name}'");
            } else {
                $this->command->info("  -> Draw #1 already exists for '{$game->name}'");
            }
        }

        $this->command->info("\n✅ All 5 games and initial draws seeded successfully!");
    }
}
