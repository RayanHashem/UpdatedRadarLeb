/**
 * Single source of truth for prize rules (UI side).
 *
 * RD:Leb. Wallet holds Radar Cash units; `games.price_to_play` is the radar amount debited per scan:
 *
 *   Mobile              min deposit 10$    implied 1 radar = 0.25$   1 scan = 1 radar
 *   Bike & Electronics  min deposit 25$    1 radar = 0.25$           1 scan = 4 radars
 *   SUV                 min deposit 30$    1 radar = 0.25$           1 scan = 8 radars
 *   Muscle Car          min deposit 40$    1 radar = 6$              1 scan = 24 radars
 *   Super Car           min deposit 50$    1 radar = 8$              1 scan = 32 radars
 *
 * Per scan, Radar Cash debited = radars per scan. Must match `Game::price_to_play`.
 *
 * Field meanings:
 *  - minDepositDollars: dollar amount shown in the insufficient-balance popup.
 *  - scanCostRadars:   Radar Cash cost per scan (must match `price_to_play` on the server).
 *  - minRadar:         legacy alias; equals minDepositDollars.
 *  - messageDisplayName: label in modals.
 */
export const PRIZE_RULES = {
    'Mobile':             { minDepositDollars: 10, scanCostRadars: 1,  minRadar: 10, displayName: 'Mobile',            messageDisplayName: 'Mobile' },
    'Bike & Electronics': { minDepositDollars: 25, scanCostRadars: 4,  minRadar: 25, displayName: 'Bike & Electronics', messageDisplayName: 'Bike / Electronics' },
    'SUV':                { minDepositDollars: 30, scanCostRadars: 8,  minRadar: 30, displayName: 'SUV',               messageDisplayName: 'SUV' },
    'Muscle Car':         { minDepositDollars: 40, scanCostRadars: 24, minRadar: 40, displayName: 'Muscle Car',        messageDisplayName: 'Muscle Car' },
    'Super Car':          { minDepositDollars: 50, scanCostRadars: 32, minRadar: 50, displayName: 'Super Car',         messageDisplayName: 'Super Car' },
    'Super Cash Prize':   { minDepositDollars: 50, scanCostRadars: 32, minRadar: 50, displayName: 'Super Car',         messageDisplayName: 'Super Car' },
};

/** Slot order matches Dashboard PRIZE_DISPLAY_CONFIG: 0=Mobile, 1=Bike/Electronics, 2=SUV, 3=Muscle Car, 4=Super. */
export const PRIZE_SLOT_RULES = [
    { minDepositDollars: 10, messageDisplayName: 'Mobile' },
    { minDepositDollars: 25, messageDisplayName: 'Bike / Electronics' },
    { minDepositDollars: 30, messageDisplayName: 'SUV' },
    { minDepositDollars: 40, messageDisplayName: 'Muscle Car' },
    { minDepositDollars: 50, messageDisplayName: 'Super Car' },
];

/**
 * @param {string} gameName - game.name from API (e.g. "Mobile", "Super Car")
 * @returns {{ minDepositDollars: number, scanCostRadars: number, minRadar: number, displayName: string, messageDisplayName: string }|null}
 */
export function getPrizeRules(gameName) {
    if (!gameName) return null;
    if (PRIZE_RULES[gameName]) return PRIZE_RULES[gameName];
    if (gameName === 'Super Cash Prize') return PRIZE_RULES['Super Car'];
    if (/super\s*(cash\s*prize|car)/i.test(String(gameName))) return PRIZE_RULES['Super Car'];
    return null;
}

/**
 * User-facing copy when wallet is below the scan cost, using the prize's minimum_deposit in $.
 * @param {string} gameName - game.name from API
 * @returns {string|null}
 */
export function getMinDepositMessage(gameName) {
    const rules = getPrizeRules(gameName);
    if (!rules) return null;
    return `You need to deposit at least ${rules.minDepositDollars}$ for a chance to win this prize.`;
}

/**
 * Same copy by slot index (0-4) when the API has no game for that slot.
 * @param {number} slotIndex
 * @returns {string}
 */
export function getMinDepositMessageBySlot(slotIndex) {
    const slot = PRIZE_SLOT_RULES[slotIndex];
    if (!slot) return 'You need to deposit before playing for this prize.';
    return `You need to deposit at least ${slot.minDepositDollars}$ for a chance to win this prize.`;
}
