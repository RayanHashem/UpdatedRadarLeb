# Game ID Migration Summary

## Problem
Users can spend on multiple games, but Filament only shows them under one game because filtering uses `users.game_id`. We need spending history per game.

## Solution
Add `game_id` to `wallet_transactions` table and filter by transaction history instead of user's current game selection.

---

## Files Changed

### A) Database Migrations

1. **`database/migrations/2026_02_19_000001_add_game_id_to_wallet_transactions_table.php`**
   - Adds `game_id` column (nullable, foreign key to `games.id`)
   - Adds indexes: `(user_id, game_id)` and `(game_id, created_at)`

2. **`database/migrations/2026_02_19_000002_backfill_game_id_in_wallet_transactions.php`**
   - Backfills `game_id` from `scans.game_id` where `scan_id` exists
   - Old rows without scan_id remain null (acceptable)

**Run migrations:**
```bash
php artisan migrate
```

---

### B) Model Updates

3. **`app/Models/WalletTransaction.php`**
   - Added `game()` relationship: `belongsTo(Game::class)`

4. **`app/Models/User.php`**
   - Added `gamesSpentOn()` relationship: `belongsToMany(Game::class)` via `wallet_transactions` where type is debit/play/spend

5. **`app/Models/Game.php`**
   - Added `walletTransactions()` relationship: `hasMany(WalletTransaction::class)`
   - Added `usersWhoSpent()` relationship: `belongsToMany(User::class)` via `wallet_transactions`
   - **Updated `attemptScan()`**: Now includes `'game_id' => $this->id` when creating wallet transaction

---

### C) Filament Resources (Users by Game Pages)

All these pages now filter by `wallet_transactions.game_id` instead of `users.game_id`:

6. **`app/Filament/Pages/MobileUsers.php`**
   - Changed query from `where('game_id', $gameId)` to `whereHas('walletTransactions', ...)`

7. **`app/Filament/Pages/BikeElectronicsUsers.php`**
   - Same change

8. **`app/Filament/Pages/SUVUsers.php`**
   - Same change

9. **`app/Filament/Pages/MuscleCarUsers.php`**
   - Same change

10. **`app/Filament/Pages/SuperCashPrizeUsers.php`**
    - Same change

**Query pattern:**
```php
->whereHas('walletTransactions', function ($query) use ($gameId) {
    $query->where('game_id', $gameId)
        ->whereIn('type', ['debit', 'play', 'spend']);
})
```

---

### D) UserResource Updates

11. **`app/Filament/Resources/UserResource.php`**
    - Form: Renamed "Prize" → "Current Game" with helper text
    - Table column: Renamed "Prize" → "Current Game"
    - Added description hint: Shows "Plays multiple games" if user has spent on multiple games

**Note:** Top-up transactions in `topoffRadarCash` action correctly leave `game_id` as `null` (topups are not game-specific).

---

## Acceptance Tests

After migration, verify:

1. **User spends on multiple games:**
   - User spends $4.00 on "Bike & Electronics" → `wallet_transactions` row has `game_id` = Bike & Electronics ID
   - Same user spends $0.50 on "Mobile" → `wallet_transactions` row has `game_id` = Mobile ID
   - User appears in **BOTH** "Bike & Electronics Users" and "Mobile Users" pages in Filament

2. **Top-ups work:**
   - Admin top-up creates transaction with `game_id = null` ✓

3. **Filtering:**
   - "Users by game" pages show users who have spent on that game (not just current selection)
   - "All Users" still shows `users.game_id` as "Current Game" (for reference)

---

## Migration Steps

1. **Backup database** (recommended)
2. Run migrations:
   ```bash
   php artisan migrate
   ```
3. Verify backfill:
   ```sql
   SELECT COUNT(*) FROM wallet_transactions WHERE game_id IS NOT NULL;
   SELECT COUNT(*) FROM wallet_transactions WHERE scan_id IS NOT NULL AND game_id IS NULL;
   ```
4. Test in Filament:
   - Check "Users by game" pages show correct users
   - Verify a user who spent on multiple games appears in multiple pages

---

## Rollback (if needed)

```bash
php artisan migrate:rollback --step=2
```

This removes `game_id` column and indexes. Note: backfill cannot be reversed (data loss).
