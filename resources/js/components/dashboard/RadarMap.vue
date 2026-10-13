<script setup lang="ts">
/*
 * Radar map of Lebanon (2026 redesign "Radar map" screen). Replaces the
 * round radar dish on the Radar screen.
 *
 * Presentation only. Dashboard.vue decides which regions are lit (one per
 * antenna found, in the order from lib/radarRegions.ts) and whether a scan
 * is running; this component draws the map, the sweep and the pins. The
 * default slot is laid over the map (status cards, "Antenna found" note).
 *
 * Everything on the map sits in a 358x404 frame (the design's map card)
 * that scales to fit the card, so the map shrinks on short phones. The
 * map never mirrors in Arabic: only the region names are translated.
 */
import RadarIcon from '@/components/dashboard/RadarIcon.vue'
import { useTranslate } from '@/composables/useTranslate'
import { REGIONS, type Region, type RegionKey } from '@/lib/radarRegions'

const props = defineProps<{
    found: RegionKey[]
    fresh: RegionKey | null
    scanning: boolean
}>()

const { t } = useTranslate()

/* Sweep centre: the radar's position on the map, as in the design. */
const CENTRE = { x: 108, y: 251 }
const RING_RADII = [60, 125, 195]

const isLit = (key: RegionKey) => props.found.includes(key)

/* Percent of the 358x404 frame, so pins follow the map when it scales. */
const at = (x: number, y: number) => ({ left: (x / 358) * 100 + '%', top: (y / 404) * 100 + '%' })
const pinPosition = (region: Region) => at(region.x, region.y)
</script>

<template>
    <div class="radar-map" :class="{ 'radar-map--scanning': scanning }" role="group" :aria-label="t('dashboard.radar.map.label')">
        <div class="radar-map__frame" dir="ltr" aria-hidden="true">
            <svg class="radar-map__shape" viewBox="0 0 358 404">
                <defs>
                    <filter id="radar-map-glow" x="-30%" y="-30%" width="160%" height="160%">
                        <feDropShadow dx="0" dy="0" stdDeviation="3" flood-color="#3CCB7A" flood-opacity="0.55" />
                    </filter>
                </defs>
                <circle
                    v-for="(radius, i) in RING_RADII"
                    :key="radius"
                    class="radar-map__ring"
                    :class="`radar-map__ring--${i + 1}`"
                    :cx="CENTRE.x"
                    :cy="CENTRE.y"
                    :r="radius"
                />
                <g transform="translate(30 46)">
                    <path
                        v-for="region in REGIONS"
                        :key="region.key"
                        class="radar-map__region"
                        :class="{ 'radar-map__region--lit': isLit(region.key), 'radar-map__region--new': region.key === fresh }"
                        :d="region.path"
                    />
                </g>
            </svg>

            <!-- Idle: faint sweep turning slowly. Scanning: full-strength sweep, one turn every 2.4 s. -->
            <div class="radar-map__sweep radar-map__sweep--idle"></div>
            <div class="radar-map__sweep radar-map__sweep--scan"></div>

            <div v-for="region in REGIONS" :key="region.key" class="radar-map__pin" :style="pinPosition(region)">
                <template v-if="isLit(region.key)">
                    <span class="radar-map__ping"></span>
                    <span class="radar-map__badge"><RadarIcon name="antenna" :size="13" :stroke="2.2" /></span>
                    <span class="radar-map__label" :class="`radar-map__label--${region.label}`">{{ t(`dashboard.radar.region.${region.key}`) }}</span>
                </template>
                <span v-else class="radar-map__dot"></span>
            </div>

            <div class="radar-map__centre" :style="at(CENTRE.x, CENTRE.y)">
                <span class="radar-map__centre-ring"></span>
                <span class="radar-map__centre-dot"></span>
            </div>
        </div>

        <!-- Sea label on the card's west edge (stays clear of the map when it shrinks). -->
        <span class="radar-map__sea" dir="ltr" aria-hidden="true">{{ t('dashboard.radar.map.sea') }}</span>

        <slot />
    </div>
</template>

<style scoped>
/* The card: grid paper background, 18px corners; sized by Dashboard (flex). */
.radar-map {
    position: relative;
    overflow: hidden;
    container-type: size;
    box-sizing: border-box;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: 18px;
    background-color: var(--rl-color-map);
    background-image:
        linear-gradient(var(--rl-color-map-grid) 1px, transparent 1px),
        linear-gradient(90deg, var(--rl-color-map-grid) 1px, transparent 1px);
    background-size: 30px 30px;
    background-position: -1px -1px;
}

/*
 * The design's 358x404 map frame, scaled to fit the card and centred.
 * Without container units (iOS 15) it stays at full size, cropped by the card.
 */
.radar-map__frame {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 358px;
    height: 404px;
    width: min(100cqw, 88.614cqh);
    height: min(100cqh, 112.849cqw);
    transform: translate(-50%, -50%);
}

.radar-map__shape {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    overflow: visible;
}

.radar-map__ring {
    fill: none;
    stroke-width: 1;
    stroke-dasharray: 3 3;
    vector-effect: non-scaling-stroke;
}

.radar-map__ring--1 { stroke: var(--rl-color-map-ring-1); }
.radar-map__ring--2 { stroke: var(--rl-color-map-ring-2); }
.radar-map__ring--3 { stroke: var(--rl-color-map-ring-3); }

.radar-map__region {
    fill: var(--rl-color-region);
    stroke: var(--rl-color-region-edge);
    stroke-width: 1;
    stroke-linejoin: round;
    vector-effect: non-scaling-stroke;
    transition: fill 0.6s, stroke 0.6s;
}

.radar-map__region--lit {
    fill: var(--rl-color-region-lit);
    stroke: var(--rl-color-found-bright);
    stroke-width: 1.4;
    filter: url(#radar-map-glow);
}

/* The region an antenna has just lit flashes bright, then settles. */
.radar-map__region--new {
    animation: radar-map-flash 1.4s ease-out;
}

/* Sweep: a 660px disc centred on the radar position (108, 251) in the frame. */
.radar-map__sweep {
    position: absolute;
    left: -62.011%;
    top: -19.554%;
    width: 184.358%;
    height: 163.366%;
    border-radius: 50%;
    background: conic-gradient(
        from 0deg,
        rgba(214, 40, 40, 0) 0deg 290deg,
        rgba(214, 40, 40, 0.1) 320deg,
        rgba(214, 40, 40, 0.4) 359deg,
        rgba(255, 110, 90, 0.95) 359.4deg,
        rgba(214, 40, 40, 0) 360deg
    );
    pointer-events: none;
    transition: opacity 0.3s ease;
}

.radar-map__sweep--idle {
    opacity: 0.22;
    animation: radar-map-sweep 10s linear infinite;
}

.radar-map__sweep--scan {
    opacity: 0;
}

.radar-map--scanning .radar-map__sweep--idle {
    opacity: 0;
}

.radar-map--scanning .radar-map__sweep--scan {
    opacity: 1;
    animation: radar-map-sweep 2.4s linear infinite;
}

.radar-map__sea {
    position: absolute;
    left: 10px;
    top: 49.5%;
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    font-family: var(--rl-font-mono);
    font-size: 9px;
    letter-spacing: 0.4em;
    text-transform: uppercase;
    white-space: nowrap;
    color: var(--rl-color-map-label);
}

/* ---- Pins -------------------------------------------------------------- */
.radar-map__pin {
    position: absolute;
    width: 0;
    height: 0;
}

.radar-map__dot {
    position: absolute;
    left: -4px;
    top: -4px;
    box-sizing: border-box;
    width: 8px;
    height: 8px;
    border: 1px solid var(--rl-color-map-pin);
    border-radius: 50%;
}

.radar-map__ping {
    position: absolute;
    width: 26px;
    height: 26px;
    margin: -13px 0 0 -13px;
    box-sizing: border-box;
    border: 1.5px solid var(--rl-color-found);
    border-radius: 50%;
    animation: radar-map-ping 2s ease-out infinite;
}

.radar-map__badge {
    position: absolute;
    left: -11px;
    top: -11px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    width: 22px;
    height: 22px;
    border: 1.5px solid var(--rl-color-found-bright);
    border-radius: 50%;
    background: var(--rl-color-night);
    color: var(--rl-color-found-bright);
}

.radar-map__label {
    position: absolute;
    top: -8px;
    padding: 2px 5px;
    border-radius: 4px;
    background: var(--rl-color-map-overlay);
    color: var(--rl-color-found-bright);
    font-family: var(--rl-font-mono);
    font-size: 9px;
    line-height: 12px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    white-space: nowrap;
}

.radar-map__label--right { left: 15px; }
.radar-map__label--left { right: 15px; }

/* Radar position: a bone dot with a slow ring, centre of the sweep and rings. */
.radar-map__centre {
    position: absolute;
    width: 0;
    height: 0;
}

.radar-map__centre-ring {
    position: absolute;
    width: 30px;
    height: 30px;
    margin: -15px 0 0 -15px;
    box-sizing: border-box;
    border: 1.5px solid rgba(237, 232, 220, 0.6);
    border-radius: 50%;
    animation: radar-map-ping 2.4s ease-out infinite;
}

.radar-map__centre-dot {
    position: absolute;
    left: -6px;
    top: -6px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--rl-color-bone);
    box-shadow: 0 0 0 4px rgba(237, 232, 220, 0.14);
}

/* ---- Arabic: sans face, no tracking (see the Radar screen notes). ------- */
.radar-map:lang(ar) .radar-map__label,
.radar-map:lang(ar) .radar-map__sea {
    font-family: var(--rl-font-sans);
    letter-spacing: 0;
}

.radar-map:lang(ar) .radar-map__label {
    font-size: 11px;
    line-height: 14px;
    top: -9px;
}

.radar-map:lang(ar) .radar-map__sea {
    font-size: 11px;
}

@keyframes radar-map-sweep {
    from { transform: rotate(38deg); }
    to { transform: rotate(398deg); }
}

@keyframes radar-map-ping {
    0% { transform: scale(0.4); opacity: 1; }
    100% { transform: scale(1.6); opacity: 0; }
}

@keyframes radar-map-flash {
    0% { fill: var(--rl-color-region-flash); }
    100% { fill: var(--rl-color-region-lit); }
}

/* Reduced motion: no idle sweep, pings or flash; the scan sweep stays. */
@media (prefers-reduced-motion: reduce) {
    .radar-map__sweep--idle {
        animation: none;
        transform: rotate(38deg);
    }

    .radar-map__ping,
    .radar-map__centre-ring {
        animation: none;
        opacity: 0.5;
    }

    .radar-map__region--new {
        animation: none;
    }
}
</style>
