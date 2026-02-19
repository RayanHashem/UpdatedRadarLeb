# Wallet Transactions Audit & Fixes

## Findings

### 1. Amount Consistency ✓
- **Current behavior**: `wallet_transactions.amount` stores positive value (the cost)
- **Scans**: `scans.cost` also stores positive value
- **Expected**: `wallet_transactions.amount` should equal `scans.cost` (both positive)
- **Status**: Code creates transaction with `amount = $cost`, which matches `scans.cost` ✓

### 2. Type Values ✓
- **Current values**: `'debit'`, `'topup'`, `'adjustment'`, `'refund'`
- **Status**: Already lowercase snake_case ✓
- **Note**: Migration added to normalize any inconsistent values if they exist

### 3. Failed Scans
- **Current behavior**: Transaction is created even if `scan.success = false`
- **Rationale**: User pays regardless of scan success (intentional)
- **Status**: No change needed (by design)

### 4. Duplicate Prevention
- **Issue**: No guard against duplicate transactions for same `scan_id`
- **Fix**: Added `firstOrCreate()` with `scan_id` as unique key
- **Migration**: Added unique constraint on `scan_id` column

---

## Files Changed

### A) Code Fixes

1. **`app/Models/Game.php`** - `attemptScan()` method
   - Changed `WalletTransaction::create()` to `WalletTransaction::firstOrCreate()`
   - Uses `scan_id` as unique key to prevent duplicates
   - Ensures `amount = $cost` matches `scans.cost`

2. **`app/Models/WalletTransaction.php`**
   - Added `boot()` method with validation:
     - Normalizes `type` to lowercase on save
     - Validates `amount` matches `scans.cost` when `scan_id` is present

### B) Database Migrations

3. **`database/migrations/2026_02_19_000003_add_unique_constraint_scan_id_to_wallet_transactions.php`**
   - Removes existing duplicates (keeps oldest per scan_id)
   - Adds unique constraint on `scan_id` column

4. **`database/migrations/2026_02_19_000004_normalize_wallet_transaction_types.php`**
   - Normalizes type values to lowercase snake_case
   - Maps variations: 'Scan', 'SCAN', 'Play', 'spent' → 'debit'
   - Maps: 'Top_up', 'TopUp', 'TOPUP' → 'topup'

5. **`database/migrations/2026_02_19_000005_fix_wallet_transaction_amount_mismatches.php`**
   - Fixes any amount mismatches: sets `wallet_transactions.amount = scans.cost`
   - Only updates rows where difference > 1 cent (rounding tolerance)

### C) Audit Queries

6. **`database/queries/audit_wallet_transactions.sql`**
   - Query 1: Amount mismatches
   - Query 2: Distinct type values
   - Query 3: Duplicate transactions per scan_id
   - Query 4: Missing game_id (should be backfilled)
   - Query 5: game_id mismatches between transactions and scans
   - Query 6: Failed scans with transactions
   - Query 7: Summary totals

---

## Migration Order

Run migrations in this order:

```bash
php artisan migrate
```

The migrations will:
1. Add unique constraint (removes duplicates first)
2. Normalize type values
3. Fix amount mismatches

---

## Validation Rules

### Application Level (WalletTransaction model)
- `type` is automatically normalized to lowercase on save
- When `scan_id` is present and `type = 'debit'`, validates `amount` matches `scans.cost` (within 1 cent tolerance)

### Database Level
- Unique constraint on `scan_id` prevents duplicate transactions per scan
- Foreign key on `game_id` ensures referential integrity

---

## SQL Audit Queries

Run `database/queries/audit_wallet_transactions.sql` to check:

1. **Amount mismatches**: Should return 0 rows after migration
2. **Type values**: Should show only: debit, topup, adjustment, refund
3. **Duplicates**: Should return 0 rows after migration
4. **Missing game_id**: Should be 0 after backfill migration
5. **game_id mismatches**: Should be 0 (transaction.game_id should match scan.game_id)
6. **Failed scans**: Shows transactions for failed scans (expected behavior)

---

## Expected Behavior After Fixes

1. **New scan transactions**:
   - `amount` always equals `scans.cost`
   - `type` is always lowercase 'debit'
   - `game_id` matches `scans.game_id`
   - No duplicates for same `scan_id`

2. **Top-up transactions**:
   - `amount` is positive (money added)
   - `type` is 'topup'
   - `game_id` is NULL (topups are not game-specific)
   - `scan_id` is NULL

3. **Failed scans**:
   - Transaction still created (user pays regardless)
   - `scan.success = false` but transaction exists

---

## Testing Checklist

- [ ] Run audit queries before migration (baseline)
- [ ] Run migrations
- [ ] Run audit queries after migration (should show 0 issues)
- [ ] Test new scan: verify transaction created with correct amount
- [ ] Test duplicate prevention: attempt to create second transaction for same scan_id (should fail or use existing)
- [ ] Test top-up: verify game_id is NULL
- [ ] Verify type normalization: try creating with uppercase type (should be normalized)
