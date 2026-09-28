<script setup lang="ts">
/*
 * Full-screen loader shown while the dashboard boots. Dashboard.vue owns
 * when it shows (its `loading` flag); this component is only the look —
 * the 2026 redesign "Loading" screen: a red arc spinning around the logo.
 */
import { useDirection, useTranslate } from '@/composables/useTranslate';

const { t, locale } = useTranslate();
const dir = useDirection();
</script>

<template>
    <div class="loading-screen" role="status" aria-live="polite">
        <div class="loading-screen__dial">
            <svg class="loading-screen__ring" width="250" height="250" viewBox="0 0 250 250" aria-hidden="true">
                <circle class="loading-screen__track" cx="125" cy="125" r="118" />
                <circle class="loading-screen__arc" cx="125" cy="125" r="118" />
            </svg>
            <img src="/assets/imgs/radarleb-logo.png" class="loading-screen__logo" width="180" height="180" alt="Radar Leb" />
        </div>
        <span class="loading-screen__label" :lang="locale" :dir="dir">{{ t('dashboard.loading') }}</span>
    </div>
</template>

<style scoped>
.loading-screen {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 28px;
    background: var(--rl-color-night);
    color: var(--rl-color-bone);
    font-family: var(--rl-font-sans);
}

.loading-screen__dial {
    position: relative;
    width: 250px;
    height: 250px;
}

.loading-screen__ring {
    position: absolute;
    inset: 0;
}

.loading-screen__track {
    fill: none;
    stroke: var(--rl-color-night-edge);
    stroke-width: 3;
}

/* 190 + 552 ≈ the circle's 741px circumference, so a quarter-ish arc shows. */
.loading-screen__arc {
    fill: none;
    stroke: var(--rl-color-signal);
    stroke-width: 3;
    stroke-linecap: round;
    stroke-dasharray: 190 552;
    transform-box: fill-box;
    transform-origin: center;
    animation: loading-screen-spin 1.6s linear infinite;
}

.loading-screen__logo {
    position: absolute;
    top: 35px;
    left: 35px;
    width: 180px;
    height: 180px;
}

.loading-screen__label {
    font-family: var(--rl-font-mono);
    font-weight: 500;
    font-size: 12px;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--rl-color-bone-muted);
}

/*
 * IBM Plex Mono has no Arabic glyphs (the fallback is a stretched monospace
 * face) and letter-spacing pulls Arabic letters apart, so Arabic gets the
 * sans stack with normal spacing.
 */
.loading-screen__label:lang(ar) {
    font-family: var(--rl-font-sans);
    letter-spacing: 0;
}

@keyframes loading-screen-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
