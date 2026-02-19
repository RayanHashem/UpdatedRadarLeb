-- Audit queries for wallet_transactions vs scans consistency

-- 1. Check for amount mismatches between wallet_transactions and scans
-- Expected: wallet_transactions.amount = scans.cost (both positive)
SELECT 
    wt.id as transaction_id,
    wt.user_id,
    wt.scan_id,
    wt.amount as transaction_amount,
    s.cost as scan_cost,
    wt.amount - s.cost as difference,
    wt.type,
    wt.created_at
FROM wallet_transactions wt
INNER JOIN scans s ON wt.scan_id = s.id
WHERE ABS(wt.amount - s.cost) > 0.01  -- Allow 1 cent tolerance for rounding
ORDER BY wt.created_at DESC;

-- 2. Check for distinct type values (should be: debit, topup, adjustment, refund)
SELECT 
    type,
    COUNT(*) as count,
    MIN(created_at) as first_occurrence,
    MAX(created_at) as last_occurrence
FROM wallet_transactions
GROUP BY type
ORDER BY count DESC;

-- 3. Check for duplicate transactions for the same scan_id
SELECT 
    scan_id,
    COUNT(*) as transaction_count,
    STRING_AGG(id::text, ', ') as transaction_ids,
    STRING_AGG(amount::text, ', ') as amounts,
    MIN(created_at) as first_transaction,
    MAX(created_at) as last_transaction
FROM wallet_transactions
WHERE scan_id IS NOT NULL
GROUP BY scan_id
HAVING COUNT(*) > 1
ORDER BY transaction_count DESC;

-- 4. Check for transactions with scan_id but missing game_id (should be backfilled)
SELECT 
    wt.id,
    wt.user_id,
    wt.scan_id,
    wt.game_id as transaction_game_id,
    s.game_id as scan_game_id,
    wt.type,
    wt.amount,
    wt.created_at
FROM wallet_transactions wt
INNER JOIN scans s ON wt.scan_id = s.id
WHERE wt.game_id IS NULL 
  AND s.game_id IS NOT NULL
ORDER BY wt.created_at DESC;

-- 5. Check for transactions with game_id mismatch between wallet_transactions and scans
SELECT 
    wt.id,
    wt.user_id,
    wt.scan_id,
    wt.game_id as transaction_game_id,
    s.game_id as scan_game_id,
    wt.type,
    wt.amount,
    wt.created_at
FROM wallet_transactions wt
INNER JOIN scans s ON wt.scan_id = s.id
WHERE wt.game_id IS NOT NULL 
  AND s.game_id IS NOT NULL
  AND wt.game_id != s.game_id
ORDER BY wt.created_at DESC;

-- 6. Check for transactions where scan failed but transaction was still created
-- (This might be intentional - user pays even if scan fails)
SELECT 
    wt.id,
    wt.user_id,
    wt.scan_id,
    s.success as scan_success,
    wt.type,
    wt.amount,
    wt.created_at
FROM wallet_transactions wt
INNER JOIN scans s ON wt.scan_id = s.id
WHERE s.success = false
  AND wt.type = 'debit'
ORDER BY wt.created_at DESC;

-- 7. Summary: Total debits vs total scan costs
SELECT 
    'Total debit transactions' as metric,
    COUNT(*) as count,
    SUM(amount) as total_amount
FROM wallet_transactions
WHERE type = 'debit' AND scan_id IS NOT NULL
UNION ALL
SELECT 
    'Total scan costs',
    COUNT(*) as count,
    SUM(cost) as total_amount
FROM scans;
