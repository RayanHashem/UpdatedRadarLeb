/**
 * Single source of truth for prize rules (min Radar Cash and scan cost).
 * All amounts are in Radar Cash units (same as users.wallet_balance).
 * minDepositDollars is for user-facing messages (minimum deposit to play for this prize).
 * Keys must match game.name from the API (e.g. "Super Cash Prize" not "Super Car").
 */
export const PRIZE_RULES = {
    'Mobile':               { minRadar: 10,  scanCostRadars: 1,  displayName: 'Mobile',               minDepositDollars: 10 },
    'Bike & Electronics':   { minRadar: 25,  scanCostRadars: 4,  displayName: 'Bike & Electronics',   minDepositDollars: 25 },
    'SUV':                  { minRadar: 30,  scanCostRadars: 8,  displayName: 'SUV',                  minDepositDollars: 30 },
    'Muscle Car':           { minRadar: 24,  scanCostRadars: 24, displayName: 'Muscle Car',           minDepositDollars: 40 },
    'Super Cash Prize':     { minRadar: 32,  scanCostRadars: 32, displayName: 'Super Car',           minDepositDollars: 50 },
};

/**
 * @param {string} gameName - game.name from API (e.g. "Mobile", "Super Cash Prize")
 * @returns {{ minRadar: number, scanCostRadars: number, displayName: string }|null}
 */
export function getPrizeRules(gameName) {
    if (!gameName) return null;
    if (PRIZE_RULES[gameName]) return PRIZE_RULES[gameName];
    if (gameName === 'Super Car') return PRIZE_RULES['Super Cash Prize'];
    if (/super\s*(cash\s*prize|car)/i.test(String(gameName))) return PRIZE_RULES['Super Cash Prize'];
    return null;
}
