/**
 * Maps a prize (game) name as stored in the database to its translation key
 * suffix, so any screen can show the translated name via
 * t(`dashboard.radar.prize.${key}`). Returns null for unknown names, in which
 * case callers show the stored name as-is.
 */
const PRIZE_KEYS: Record<string, string> = {
    'Mobile': 'mobile',
    'Bike & Electronics': 'bike',
    'SUV': 'suv',
    'Muscle Car': 'muscle',
    'Super Car': 'supercar',
    'Super Cash Prize': 'supercar',
};

export function prizeKey(name: string | null | undefined): string | null {
    if (!name) return null;
    const trimmed = String(name).trim();
    if (PRIZE_KEYS[trimmed]) return PRIZE_KEYS[trimmed];
    // Same loose match Dashboard.vue uses for the fifth slot.
    if (/super\s*(cash\s*prize|car)/i.test(trimmed)) return 'supercar';
    return null;
}
