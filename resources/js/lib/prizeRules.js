/**
 * Single source of truth for prize rules (min Radar Cash and scan cost).
 * minDepositDollars is the minimum deposit required to play for this prize (used for eligibility and popup message).
 * messageDisplayName is the exact label used in the "Minimum deposit is X$ to play for Y." popup.
 * Keys must match game.name from the API (e.g. "Super Cash Prize" not "Super Car").
 */
export const PRIZE_RULES = {
    'Mobile':               { minRadar: 10,  scanCostRadars: 1,  displayName: 'Mobile',               minDepositDollars: 10,  messageDisplayName: 'Mobile' },
    'Bike & Electronics':   { minRadar: 25,  scanCostRadars: 4,  displayName: 'Bike & Electronics',   minDepositDollars: 25,  messageDisplayName: 'Bike / Electronics' },
    'SUV':                  { minRadar: 30,  scanCostRadars: 8,  displayName: 'SUV',                  minDepositDollars: 30,  messageDisplayName: 'SUV' },
    'Muscle Car':           { minRadar: 24,  scanCostRadars: 24, displayName: 'Muscle Car',           minDepositDollars: 40,  messageDisplayName: 'Muscle Car' },
    'Super Cash Prize':     { minRadar: 32,  scanCostRadars: 32, displayName: 'Super Car',           minDepositDollars: 50,  messageDisplayName: 'Super Car / Cash Prize' },
};

/** Slot order matches Dashboard PRIZE_DISPLAY_CONFIG: 0=Mobile, 1=Bike/Electronics, 2=SUV, 3=Muscle Car, 4=Super. */
export const PRIZE_SLOT_RULES = [
    { minDepositDollars: 10,  messageDisplayName: 'Mobile' },
    { minDepositDollars: 25,  messageDisplayName: 'Bike / Electronics' },
    { minDepositDollars: 30,  messageDisplayName: 'SUV' },
    { minDepositDollars: 40,  messageDisplayName: 'Muscle Car' },
    { minDepositDollars: 50,  messageDisplayName: 'Super Car / Cash Prize' },
];

/**
 * @param {string} gameName - game.name from API (e.g. "Mobile", "Super Cash Prize")
 * @returns {{ minRadar: number, scanCostRadars: number, displayName: string, minDepositDollars: number, messageDisplayName: string }|null}
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
