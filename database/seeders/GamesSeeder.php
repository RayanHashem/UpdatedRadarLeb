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
        $games = [
            [
                'name' => 'Mobile',
                'price' => 500.00, // Prize value
                'price_to_play' => 0.25,
                'minimum_deposit' => 10.00,
                'minimum_amount_for_winning' => 1,
            ],
            [
                'name' => 'Bike & Electronics',
                'price' => 2000.00,
                'price_to_play' => 4.00,
                'minimum_deposit' => 25.00,
                'minimum_amount_for_winning' => 1,
            ],
            [
                'name' => 'SUV',
                'price' => 50000.00,
                'price_to_play' => 16.00,
                'minimum_deposit' => 30.00,
                'minimum_amount_for_winning' => 1,
            ],
            [
                'name' => 'Muscle Car',
                'price' => 100000.00,
                'price_to_play' => 144.00,
                'minimum_deposit' => 40.00,
                'minimum_amount_for_winning' => 1,
            ],
            [
                'name' => 'Super Cash Prize',
                'price' => 500000.00,
                'price_to_play' => 256.00,
                'minimum_deposit' => 50.00,
                'minimum_amount_for_winning' => 1,
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
