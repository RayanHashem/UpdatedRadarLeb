/**
 * Single source of truth for prize rules (UI side).
 *
 * Matches the server-side `games` table (see database/seeders/GamesSeeder.php):
 *
 *   Mobile              min deposit 10$   1 radar = 0.25$    1 scan = 1 radar    → 0.25$ / scan
 *   Bike & Electronics  min deposit 25$   1 radar = 1$       1 scan = 4 radars   → 4$ / scan
 *   SUV                 min deposit 30$   1 radar = 2$       1 scan = 8 radars   → 16$ / scan
 *   Muscle Car          min deposit 40$   1 radar = 6$       1 scan = 24 radars  → 144$ / scan
 *   Super Cash Prize    min deposit 50$   1 radar = 8$       1 scan = 32 radars  → 256$ / scan
 *
 * Field meanings:
 *  - minDepositDollars: gate for SELECTING the prize button.
 *  - scanCostDollars:   exact $ cost deducted from wallet per scan (Game::price_to_play).
 *  - minRadar:          legacy alias kept to avoid breaking callers; equals minDepositDollars.
 *  - scanCostRadars:    radars per scan (purely informational for the UI).
 *  - messageDisplayName: exact label used in the "Minimum deposit is X$ to play for Y." popup.
 */
export const PRIZE_RULES = {
    'Mobile':             { minDepositDollars: 10, scanCostDollars: 0.25, scanCostRadars: 1,  minRadar: 10, displayName: 'Mobile',            messageDisplayName: 'Mobile' },
    'Bike & Electronics': { minDepositDollars: 25, scanCostDollars: 4,    scanCostRadars: 4,  minRadar: 25, displayName: 'Bike & Electronics', messageDisplayName: 'Bike / Electronics' },
    'SUV':                { minDepositDollars: 30, scanCostDollars: 16,   scanCostRadars: 8,  minRadar: 30, displayName: 'SUV',               messageDisplayName: 'SUV' },
    'Muscle Car':         { minDepositDollars: 40, scanCostDollars: 144,  scanCostRadars: 24, minRadar: 40, displayName: 'Muscle Car',        messageDisplayName: 'Muscle Car' },
    'Super Cash Prize':   { minDepositDollars: 50, scanCostDollars: 256,  scanCostRadars: 32, minRadar: 50, displayName: 'Super Car',         messageDisplayName: 'Super Car / Cash Prize' },
};

/** Slot order matches Dashboard PRIZE_DISPLAY_CONFIG: 0=Mobile, 1=Bike/Electronics, 2=SUV, 3=Muscle Car, 4=Super. */
export const PRIZE_SLOT_RULES = [
    { minDepositDollars: 10, messageDisplayName: 'Mobile' },
    { minDepositDollars: 25, messageDisplayName: 'Bike / Electronics' },
    { minDepositDollars: 30, messageDisplayName: 'SUV' },
    { minDepositDollars: 40, messageDisplayName: 'Muscle Car' },
    { minDepositDollars: 50, messageDisplayName: 'Super Car / Cash Prize' },
];

/**
 * @param {string} gameName - game.name from API (e.g. "Mobile", "Super Cash Prize")
 * @returns {{ minDepositDollars: number, scanCostDollars: number, scanCostRadars: number, minRadar: number, displayName: string, messageDisplayName: string }|null}
 */
export function getPrizeRules(gameName) {
    if (!gameName) return null;
    if (PRIZE_RULES[gameName]) return PRIZE_RULES[gameName];
    if (gameName === 'Super Car') return PRIZE_RULES['Super Cash Prize'];
    if (/super\s*(cash\s*prize|car)/i.test(String(gameName))) return PRIZE_RULES['Super Cash Prize'];
    return null;
}

/**
 * Returns the "Minimum deposit is X$ to play for Y." message for a game (by name).
 * @param {string} gameName - game.name from API
 * @returns {string|null}
 */
export function getMinDepositMessage(gameName) {
    const rules = getPrizeRules(gameName);
    if (!rules) return null;
    return `Minimum deposit is ${rules.minDepositDollars}$ to play for ${rules.messageDisplayName}.`;
}

/**
 * Returns the same message by slot index (0-4). Use when prize from API is missing for that slot.
 * @param {number} slotIndex - 0=Mobile, 1=Bike/Electronics, 2=SUV, 3=Muscle Car, 4=Super
 * @returns {string}
 */
export function getMinDepositMessageBySlot(slotIndex) {
    const slot = PRIZE_SLOT_RULES[slotIndex];
    if (!slot) return 'Minimum deposit required to play for this prize.';
    return `Minimum deposit is ${slot.minDepositDollars}$ to play for ${slot.messageDisplayName}.`;
}
