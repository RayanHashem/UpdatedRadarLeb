<script setup lang="ts">
/*
 * Static content pages (Terms / Privacy / Credits).
 *
 * One component, three modes — they share the same chrome (.page-login bg,
 * dark glass form container, pill CTA) so the user doesn't get yanked into
 * a different design language when they tap the link. /terms shows the full
 * Terms & Conditions (including Privacy Policy incorporation by reference);
 * /privacy remains a shorter placeholder unless updated separately.
 */
import { Link } from '@inertiajs/vue3';
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
    <section id="sign-in" class="w-100 page-login">
        <div class="d-flex align-items-center flex-column h-100 p-3-5">
            <div class="d-flex gap-4 flex-column w-100 align-items-center">
                <img src="/assets/imgs/Flag_of_Lebanon.png" class="flag" alt="Flag of Lebanon" />
            </div>

            <div
                class="legal-shell form-container d-flex flex-column gap-4"
                :class="{ 'legal-shell--terms': type === 'terms' }"
            >
                <h1 v-if="type !== 'terms'" class="legal-title text-center mb-0">{{ title }}</h1>

                <!-- Full Terms & Privacy (by reference): compact, scrollable on mobile -->
                <div v-if="type === 'terms'" class="terms-scroll-panel">
                    <pre class="terms-full-text">{{ RADARLEB_TERMS_FULL }}</pre>
                </div>

                <!-- Terms / Privacy: placeholder copy for now -->
                <p v-else-if="type === 'privacy'" class="legal-body mb-0">
                    Privacy policy content will be available here. This is a placeholder.
                </p>

                <!-- Credits: real attribution -->
                <div v-else class="credits-body">
                    <p class="legal-body mb-3">
                        Radar Leb is a Lebanese-themed sweepstakes web app. Thanks to
                        everyone who helped bring it to life.
                    </p>

                    <div class="credits-section">
                        <h2 class="credits-section__title">Built by</h2>
                        <p class="credits-section__body">Rayan Hashem</p>
                    </div>

                    <div class="credits-section">
                        <h2 class="credits-section__title">Cleanup &amp; polish</h2>
                        <p class="credits-section__body">
                            Abed El-Fattah Amouneh — codebase cleanup, security hardening,
                            performance optimization, Figma adaptation, and AI-tooling setup.
                        </p>
                    </div>

                    <div class="credits-section">
                        <h2 class="credits-section__title">Built with</h2>
                        <p class="credits-section__body">
                            Laravel, Filament, Inertia, Vue 3, Tailwind, Vite. Hosted on
                            Render with PostgreSQL on AWS RDS.
                        </p>
                    </div>
                </div>

                <Link
                    :href="backHref"
                    class="btn btn-custom-2 btn-custom mt-2 legal-back-btn"
                >
                    {{ backLabel }}
                </Link>
            </div>
        </div>
    </section>
</template>

<style scoped>
/*
 * Cap reading width like a print column rather than letting the line stretch
 * across the whole viewport on desktop — long lines tank legibility for
 * legal copy in particular. The dark glass backdrop matters here too: the
 * .page-login background is a busy photo, and .form-container is transparent
 * by default (the Login page leans on its dark input fields for visual
 * structure). Without a backdrop the text floats illegibly on the photo.
 */
.legal-shell {
    width: 100%;
    max-width: 36rem;
    background: rgba(6, 33, 46, 0.78);
    border: 1px solid rgba(98, 195, 255, 0.18);
    border-radius: 20px;
    padding: 1.75rem 1.5rem;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.legal-title {
    color: var(--rl-color-text);
    font-family: var(--rl-font-display);
    font-size: var(--rl-fs-h1);
    letter-spacing: 0.02em;
}

.legal-body {
    color: var(--rl-color-text-muted);
    font-size: var(--rl-fs-body);
    line-height: 1.6;
}

/*
 * `.btn-custom` sets flex: 1 — in a column flex (this shell), that stretches
 * the back control to fill leftover height. Chain both classes to beat it.
 */
.legal-back-btn.btn-custom {
    flex: 0 0 auto;
    width: fit-content;
    max-width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: unset;
    background-color: var(--rl-color-cyan);
    color: var(--rl-color-text-on-light);
    align-self: center;
}

.credits-body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.credits-section__title {
    color: var(--rl-color-cyan);
    font-family: var(--rl-font-display);
    font-size: var(--rl-fs-h2);
    margin: 0 0 0.25rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.credits-section__body {
    color: var(--rl-color-text-muted);
    font-size: var(--rl-fs-body);
    line-height: 1.55;
    margin: 0;
}

/* Full terms: small type, scrollable body (mobile-friendly). */
.legal-shell--terms {
    flex: 1 1 auto;
    min-height: 0;
    max-height: min(92vh, 52rem);
}

.terms-scroll-panel {
    flex: 1 1 auto;
    min-height: 10rem;
    max-height: min(68vh, 26rem);
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    padding: 0.6rem 0.75rem;
    border-radius: 12px;
    background: rgba(0, 0, 0, 0.22);
    border: 1px solid rgba(98, 195, 255, 0.12);
}

.terms-full-text {
    margin: 0;
    padding: 0;
    font-family: inherit;
    font-size: 0.6875rem;
    line-height: 1.45;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    color: var(--rl-color-text-muted);
}
</style>
