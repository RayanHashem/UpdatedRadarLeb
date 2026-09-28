<script setup lang="ts">
/*
 * How to play page shown over the Radar screen (2026 redesign "Help / how
 * to play" screen). Opens from the "?" icon, the Radar Cash tile, the
 * "How to play" popup button and after sign-up.
 *
 * All copy comes from lang/<locale>.json via useTranslate(), word for word as
 * before. Step 2 contains the support phone via `{phone}` interpolation — the
 * value lives in config/contact.php and is shared on every Inertia request as
 * `contact.phone`.
 *
 * Pure presentation: no fetches, no mutations. Emits `close` when the user
 * dismisses; the parent decides what that means.
 */
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import RadarIcon from '@/components/dashboard/RadarIcon.vue'
import { useTranslate } from '@/composables/useTranslate'

const emit = defineEmits<{
    (e: 'close'): void
}>()

const { t } = useTranslate()
const supportPhone = computed(() => usePage().props.contact?.phone ?? '')

const steps = computed(() => [
    t('dashboard.help.step1'),
    t('dashboard.help.step2', { phone: supportPhone.value }),
    t('dashboard.help.step3'),
    t('dashboard.help.step4'),
    t('dashboard.help.step5'),
    t('dashboard.help.step6'),
])

/*
 * The notes string starts with its own label ("Notes: …" / "ملاحظة: …"). The
 * design shows that label as a small heading, so split at the first colon
 * instead of changing the copy.
 */
const notes = computed(() => {
    const text = t('dashboard.help.notes')
    const colon = text.indexOf(':')
    if (colon <= 0 || colon > 20) return { label: '', body: text }
    return { label: text.slice(0, colon).trim(), body: text.slice(colon + 1).trim() }
})
</script>

<template>
    <div class="rl-sheet" role="dialog" aria-modal="true" aria-labelledby="help-title">
        <div class="rl-sheet__column help">
            <div class="rl-sheet__bar">
                <span class="rl-sheet__brand">{{ t('dashboard.radar.help') }}</span>
                <button type="button" class="rl-sheet__close" :aria-label="t('dashboard.popup.close')" @click="emit('close')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <h1 id="help-title" class="rl-title rl-sheet__title help__title">{{ t('dashboard.help.title') }}</h1>
            <p class="help__intro">{{ t('dashboard.help.intro') }}</p>

            <h2 class="rl-title help__subtitle">{{ t('dashboard.help.howToTitle') }}</h2>
            <ol class="help__steps">
                <li v-for="(step, i) in steps" :key="i" class="help__step" :style="{ animationDelay: 80 + i * 50 + 'ms' }">
                    <span class="help__step-number" aria-hidden="true">{{ String(i + 1).padStart(2, '0') }}</span>
                    <span>{{ step }}</span>
                </li>
            </ol>

            <div class="help__notes">
                <strong v-if="notes.label" class="help__notes-label">{{ notes.label }}</strong>
                <span class="help__notes-body">{{ notes.body }}</span>
            </div>

            <div class="help__tools">
                <div class="help__tool">
                    <div class="help__tool-icon" aria-hidden="true">
                        <RadarIcon name="pin" />
                    </div>
                    <div class="help__tool-copy">
                        <h3 class="help__tool-title">{{ t('dashboard.help.locationTitle') }}</h3>
                        <p class="help__tool-text">{{ t('dashboard.help.locationText') }}</p>
                    </div>
                </div>
                <div class="help__tool">
                    <div class="help__tool-icon" aria-hidden="true">
                        <RadarIcon name="wallet" />
                    </div>
                    <div class="help__tool-copy">
                        <h3 class="help__tool-title">{{ t('dashboard.help.radarCashTitle') }}</h3>
                        <p class="help__tool-text">{{ t('dashboard.help.radarCashText') }}</p>
                    </div>
                </div>
            </div>

            <div class="rl-sheet__spacer"></div>
            <button type="button" class="rl-button rl-sheet__back help__done" @click="emit('close')">
                {{ t('dashboard.help.gotIt') }}
            </button>
        </div>
    </div>
</template>

<style scoped>
/* The design's frame uses 20px side padding here (16px on other sheets). */
.help {
    padding-right: 20px;
    padding-left: 20px;
    padding-bottom: calc(28px + env(safe-area-inset-bottom, 0px));
}

.help__title {
    margin-bottom: 14px;
}

.help__intro {
    margin: 0;
    font-size: 15px;
    line-height: 1.6;
    color: var(--rl-color-bone-soft);
}

.help__subtitle {
    margin: 28px 0 14px;
    font-weight: 700;
    font-size: 26px;
    line-height: 1.1;
}

.help__steps {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.help__step {
    display: grid;
    grid-template-columns: 32px minmax(0, 1fr);
    gap: 12px;
    font-size: 15px;
    line-height: 1.55;
    color: var(--rl-color-bone-soft);
    animation: help-in 260ms ease-out both;
}

.help__step-number {
    padding-top: 2px;
    font-family: var(--rl-font-mono);
    font-size: 13px;
    color: var(--rl-color-alert);
}

.help__notes {
    margin-top: 22px;
    padding: 14px 16px;
    border: 1px solid var(--rl-color-dialog-edge);
    border-radius: 14px;
    background: var(--rl-color-night-raised);
    font-size: 14px;
    line-height: 1.55;
    color: var(--rl-color-bone-soft);
}

.help__notes-label {
    display: block;
    margin-bottom: 6px;
    font-family: var(--rl-font-mono);
    font-weight: 500;
    font-size: 11px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--rl-color-bone-subtle);
}

/* "scan from…" follows the colon in the copy; the design starts it with a capital. */
.help__notes-body {
    display: block;
}

.help__notes-body::first-letter {
    text-transform: uppercase;
}

.help__tools {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 14px;
}

.help__tool {
    display: flex;
    gap: 14px;
    padding: 16px;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: 14px;
    background: var(--rl-color-night-raised);
}

.help__tool-icon {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    width: 44px;
    height: 44px;
    border: 1px solid var(--rl-color-night-line);
    border-radius: 12px;
    color: var(--rl-color-bone);
}

.help__tool-copy {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.help__tool-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--rl-color-bone);
}

.help__tool-text {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: var(--rl-color-dialog-text);
}

.help__done {
    height: 52px;
    font-size: 20px;
}

/* Arabic: no mono/tracking on the notes label; a touch more line height for Arabic script. */
.rl-sheet:lang(ar) .help__notes-label {
    font-family: var(--rl-font-sans);
    font-size: 12px;
    letter-spacing: 0;
}

.rl-sheet:lang(ar) .help__subtitle {
    line-height: 1.3;
}

.rl-sheet:lang(ar) .help__done {
    letter-spacing: 0;
}

@keyframes help-in {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: none; }
}

@media (prefers-reduced-motion: reduce) {
    .help__step {
        animation: none;
    }
}
</style>
