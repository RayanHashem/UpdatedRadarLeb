# Wallet Transactions Audit - Summary

## Critical Findings & Fixes

### ✅ 1. Amount Consistency
**Status**: **CORRECT** - Code already ensures `wallet_transactions.amount = scans.cost`

- In `Game::attemptScan()`: `amount => $cost` matches `scans.cost`
- Both store positive values (cost, not negative deduction)
- Migration added to fix any existing mismatches

### ✅ 2. Type Values
**Status**: **NORMALIZED** - Values are lowercase snake_case

- Current: `'debit'`, `'topup'`, `'adjustment'`, `'refund'`
- Model validation: Auto-normalizes to lowercase on save
- Migration: Normalizes any inconsistent historical values

### ⚠️ 3. Failed Scans
**Status**: **INTENTIONAL** - Transactions created even if scan fails

- User pays regardless of scan success (`scan.success = false`)
- This is by design (no fix needed)

### ✅ 4. Duplicate Prevention
**Status**: **FIXED** - Added guard against duplicates

- Changed `create()` to `firstOrCreate()` using `scan_id` as key
- Database: Added unique partial index on `scan_id` (where NOT NULL)

---

## Files Modified

### Code Changes

1. **`app/Models/Game.php`**
   ```php
   // BEFORE:
   WalletTransaction::create([...]);
   
   // AFTER:
   WalletTransaction::firstOrCreate(
       ['scan_id' => $scan->id], // Prevents duplicates
       [...]
   );
   ```

2. **`app/Models/WalletTransaction.php`**
   - Added `boot()` method with:
     - Type normalization (lowercase)
     - Amount validation (must match `scans.cost` when `scan_id` present)

### Database Migrations

3. **`2026_02_19_000003_add_unique_constraint_scan_id_to_wallet_transactions.php`**
   - Removes duplicates (keeps oldest per `scan_id`)
   - Adds unique partial index on `scan_id`

4. **`2026_02_19_000004_normalize_wallet_transaction_types.php`**
   - Normalizes type values to lowercase snake_case
   - Maps: 'Scan', 'SCAN', 'Play', 'spent' → 'debit'
   - Maps: 'Top_up', 'TopUp', 'TOPUP' → 'topup'

5. **`2026_02_19_000005_fix_wallet_transaction_amount_mismatches.php`**
   - Fixes amount mismatches: sets `amount = scans.cost`
   - Only updates where difference > 1 cent

### Audit Queries

6. **`database/queries/audit_wallet_transactions.sql`**
   - 7 queries to detect:
     - Amount mismatches
     - Type inconsistencies
     - Duplicate transactions
     - Missing/mismatched game_id
     - Failed scans with transactions
     - Summary totals

---

## Migration Order

```bash
# Run all migrations
php artisan migrate

# Or run individually:
php artisan migrate --path=database/migrations/2026_02_19_000003_add_unique_constraint_scan_id_to_wallet_transactions.php
php artisan migrate --path=database/migrations/2026_02_19_000004_normalize_wallet_transaction_types.php
php artisan migrate --path=database/migrations/2026_02_19_000005_fix_wallet_transaction_amount_mismatches.php
```

---

## Validation Rules

### Application Level
- **Type**: Auto-normalized to lowercase on save
- **Amount**: Validated to match `scans.cost` when `scan_id` present (on create only)

### Database Level
- **Unique constraint**: One transaction per `scan_id` (partial index, NULLs allowed)
- **Foreign keys**: `scan_id` → `scans.id`, `game_id` → `games.id`

---

## SQL Audit Queries

Run `database/queries/audit_wallet_transactions.sql`:

1. **Amount mismatches** → Should return 0 after migration
2. **Type values** → Should show only: debit, topup, adjustment, refund
3. **Duplicates** → Should return 0 after migration
4. **Missing game_id** → Should be 0 after backfill
5. **game_id mismatches** → Should be 0
6. **Failed scans** → Shows transactions for failed scans (expected)
7. **Summary totals** → Compare total debits vs total scan costs

---

## Expected Behavior

### New Scan Transaction
- `amount` = `scans.cost` (positive, matches exactly)
- `type` = 'debit' (lowercase)
- `game_id` = `scans.game_id`
- `scan_id` = scan ID
- **No duplicates** (unique constraint)

### Top-up Transaction
- `amount` = positive (money added)
- `type` = 'topup'
- `game_id` = NULL
- `scan_id` = NULL

### Failed Scan
- Transaction still created (user pays regardless)
- `scan.success` = false but transaction exists

---

## Testing

1. **Run audit queries** before migration (baseline)
2. **Run migrations**
3. **Run audit queries** after migration (should show 0 issues)
4. **Test new scan**: Verify transaction created with correct amount
5. **Test duplicate prevention**: Attempt second transaction for same scan_id (should use existing)
6. **Test type normalization**: Try uppercase type (should be normalized)
7. **Test amount validation**: Try wrong amount (should throw exception)
