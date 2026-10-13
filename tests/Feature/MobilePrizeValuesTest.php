<?php

namespace Tests\Feature;

use App\Models\Game;
use Database\Seeders\GameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mobile prize raised to $2,000 (2026-10): price, target and win threshold
 * are 2400 Radar Cash. The migration updates an existing database (live);
 * the seeder sets the same values on a fresh one. Scan cost and minimum
 * deposit must not move.
 */
class MobilePrizeValuesTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(string $direction): void
    {
        $migration = require database_path('migrations/2026_10_13_000001_raise_mobile_prize_to_2400.php');
        $migration->{$direction}();
    }

    public function test_migration_raises_mobile_to_2400_and_leaves_other_values_alone(): void
    {
        $mobile = Game::factory()->create([
            'name' => 'Mobile',
            'price' => 1800,
            'target_amount' => 1800,
            'minimum_amount_for_winning' => 1800,
            'price_to_play' => 1,
            'minimum_deposit' => 10,
        ]);
        $bike = Game::factory()->create([
            'name' => 'Bike & Electronics',
            'price' => 18000,
            'target_amount' => 18000,
            'minimum_amount_for_winning' => 18000,
        ]);

        $this->runMigration('up');

        $mobile->refresh();
        $this->assertEquals(2400, (float) $mobile->price);
        $this->assertEquals(2400, (float) $mobile->target_amount);
        $this->assertEquals(2400, (float) $mobile->minimum_amount_for_winning);
        $this->assertEquals(1, (float) $mobile->price_to_play);
        $this->assertEquals(10, (float) $mobile->minimum_deposit);

        $bike->refresh();
        $this->assertEquals(18000, (float) $bike->price);
        $this->assertEquals(18000, (float) $bike->minimum_amount_for_winning);

        $this->runMigration('down');

        $mobile->refresh();
        $this->assertEquals(1800, (float) $mobile->price);
        $this->assertEquals(1800, (float) $mobile->minimum_amount_for_winning);
    }

    public function test_seeder_creates_mobile_with_the_same_values(): void
    {
        $this->seed(GameSeeder::class);

        $mobile = Game::where('name', 'Mobile')->firstOrFail();
        $this->assertEquals(2400, (float) $mobile->price);
        $this->assertEquals(2400, (float) $mobile->target_amount);
        $this->assertEquals(2400, (float) $mobile->minimum_amount_for_winning);
        $this->assertEquals(1, (float) $mobile->price_to_play);
        $this->assertEquals(10, (float) $mobile->minimum_deposit);
    }
}
