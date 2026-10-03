<script setup lang="ts">
/*
 * Static content pages (Terms / Privacy / Credits) — Sign in style (2026
 * redesign). Not part of the client design, so they reuse the Sign in
 * screen's rings, logo and heading. English only, like before.
 *
 * One component, three modes. /terms shows the full Terms & Conditions
 * (including Privacy Policy incorporation by reference) in a scrollable
 * panel; /privacy remains a shorter placeholder unless updated separately.
 */
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { RADARLEB_TERMS_FULL } from '@/content/radarlebTermsFull';

const props = defineProps<{
    type: 'terms' | 'privacy' | 'credits';
}>();

const title = computed(() => {
    if (props.type === 'terms') return 'Terms & Conditions';
    if (props.type === 'privacy') return 'Privacy Policy';
    return 'Credits';
});

/**
 * Where "back" goes depends on the page type. Legal pages are reached from
 * /register (during sign-up). Credits is reached from the dashboard Settings
 * overlay, so we point back to the dashboard.
 */
const backHref = computed(() => props.type === 'credits' ? route('dashboard') : route('register'));
const backLabel = computed(() => props.type === 'credits' ? 'Back to game' : 'Back to Sign up');
</script>

<template>
    <Head :title="title" />
    <main class="rl-screen">
        <span class="rl-ring rl-hero__ring rl-hero__ring--outer" aria-hidden="true"></span>
        <span class="rl-ring rl-hero__ring rl-hero__ring--mid" aria-hidden="true"></span>
        <span class="rl-ring rl-hero__ring rl-hero__ring--inner" aria-hidden="true"></span>

        <div class="rl-screen__column">
            <img src="/assets/imgs/radarleb-logo.png" class="rl-hero__logo" width="176" height="176" alt="Radar Leb" />

            <div class="rl-hero__heading">
                <h1 id="legal-title" class="rl-title rl-hero__title">{{ title }}</h1>
            </div>

            <!-- Full Terms & Privacy (by reference): fills the screen and scrolls on its own, so Back stays in view. -->
            <div v-if="type === 'terms'" class="legal__panel" tabindex="0" role="region" aria-labelledby="legal-title">
                <pre class="legal__terms">{{ RADARLEB_TERMS_FULL }}</pre>
            </div>

            <!-- Privacy: placeholder copy for now -->
            <p v-else-if="type === 'privacy'" class="legal__body">
                Privacy policy content will be available here. This is a placeholder.
            </p>

            <!-- Credits: real attribution -->
            <div v-else class="legal__credits">
                <p class="legal__body">
                    Radar Leb is a Lebanese-themed sweepstakes web app. Thanks to
                    everyone who helped bring it to life.
                </p>

                <div class="legal__card">
                    <div class="legal__section">
                        <h2 class="rl-label legal__section-title">Built by</h2>
                        <p class="legal__section-body">Rayan Hashem</p>
                    </div>

                    <div class="legal__section">
                        <h2 class="rl-label legal__section-title">Cleanup &amp; polish</h2>
                        <p class="legal__section-body">
                            Abed El-Fattah Amouneh — codebase cleanup, security hardening,
                            performance optimization, Figma adaptation, and AI-tooling setup.
                        </p>
                    </div>

                    <div class="legal__section">
                        <h2 class="rl-label legal__section-title">Built with</h2>
                        <p class="legal__section-body">
                            Laravel, Filament, Inertia, Vue 3, Tailwind, Vite. Hosted on
                            Render with PostgreSQL on AWS RDS.
                        </p>
                    </div>
                </div>
            </div>

            <div class="legal__actions">
                <Link :href="backHref" class="rl-button rl-button--secondary">{{ backLabel }}</Link>
            </div>
        </div>
    </main>
</template>

<style scoped>
.legal__body {
    margin: 20px 0 0;
    font-size: 15px;
    line-height: 1.6;
    color: var(--rl-color-bone-soft);
}

/*
 * Terms: same inset panel as the Sign up terms dialog. flex-basis 0 lets it
 * take whatever height is left above Back; on short phones it keeps 260px
 * and the page scrolls a little instead.
 */
.legal__panel {
    flex: 1 1 0;
    min-height: 260px;
    margin-top: 20px;
    padding: 12px 14px;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: var(--rl-radius-field);
    background: var(--rl-color-night-sunken);
}

.legal__panel:focus-visible {
    outline: 2px solid var(--rl-color-bone);
    outline-offset: 2px;
}

.legal__terms {
    margin: 0;
    padding: 0;
    font-family: inherit;
    font-size: 13px;
    line-height: 1.5;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    color: var(--rl-color-bone-soft);
}

/* Credits: intro line, then one card with a section per credit. */
.legal__card {
    margin-top: 20px;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: 16px;
    background: var(--rl-color-night-raised);
}

.legal__section {
    padding: 14px 16px;
}

.legal__section + .legal__section {
    border-top: 1px solid var(--rl-color-night-edge);
}

.legal__section-title {
    display: block;
    margin: 0 0 4px;
    line-height: 1.4;
}

.legal__section-body {
    margin: 0;
    font-size: 15px;
    line-height: 1.55;
    color: var(--rl-color-bone);
}

/* margin-top:auto pins Back to the bottom of short pages. */
.legal__actions {
    flex-shrink: 0;
    margin-top: auto;
    padding-top: 24px;
}
</style>
