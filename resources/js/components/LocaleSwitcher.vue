<script setup lang="ts">
/*
 * Tiny language switcher. Renders one button per supported locale (read from
 * the shared prop `locales`). Posts to /locale/{locale}, then Inertia reloads
 * the current page with `preserveScroll: true` so the user doesn't lose their
 * place on the dashboard.
 *
 * Drop into any layout / header where a language toggle belongs — see
 * resources/js/layouts/AppLayout.vue for a usage example.
 */
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

const { locale, locales, t } = useTranslate()

const options = computed(() => Object.entries(locales.value).map(([code, label]) => ({
    code,
    label,
    isActive: code === locale.value,
})))

function setLocale(code: string) {
    if (code === locale.value) return
    router.post(
        `/locale/${code}`,
        {},
        { preserveScroll: true, preserveState: false },
    )
}
</script>

<template>
    <div class="locale-switcher" :aria-label="t('locale.switch')">
        <button
            v-for="opt in options"
            :key="opt.code"
            type="button"
            class="locale-switcher__btn"
            :class="{ 'is-active': opt.isActive }"
            :aria-pressed="opt.isActive"
            :title="opt.label"
            @click="setLocale(opt.code)"
        >
            {{ opt.code.toUpperCase() }}
        </button>
    </div>
</template>

<style scoped>
/* 2026 redesign segmented EN/AR control (Radar screen header). */
.locale-switcher {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--rl-color-night-line);
    border-radius: 8px;
    user-select: none;
}

.locale-switcher__btn {
    position: relative;
    appearance: none;
    width: 34px;
    height: 30px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    color: var(--rl-color-bone-muted);
    font-family: var(--rl-font-mono);
    font-size: 12px;
    cursor: pointer;
}

.locale-switcher__btn.is-active {
    background: var(--rl-color-bone);
    color: var(--rl-color-night);
}

/* Round the outer ends (instead of clipping the pill, so the tap areas below can reach outside it). */
.locale-switcher__btn:first-child {
    border-start-start-radius: 7px;
    border-end-start-radius: 7px;
}

.locale-switcher__btn:last-child {
    border-start-end-radius: 7px;
    border-end-end-radius: 7px;
}

/*
 * Invisible 44x44 tap area: 7px above and below, and 10px outwards
 * (EN and AR still meet in the middle, so they never overlap).
 */
.locale-switcher__btn::before {
    content: '';
    position: absolute;
    top: -7px;
    bottom: -7px;
    inset-inline: 0;
}

.locale-switcher__btn:first-child::before {
    inset-inline-start: -10px;
}

.locale-switcher__btn:last-child::before {
    inset-inline-end: -10px;
}

/* Press feedback: snaps in, releases slowly so a quick tap still shows. */
.locale-switcher__btn {
    transition: background-color 220ms ease-out, transform 220ms ease-out;
}

.locale-switcher__btn:active {
    background: var(--rl-color-press-wash);
    transform: scale(0.9);
    transition-duration: 60ms;
}

.locale-switcher__btn.is-active:active {
    background: var(--rl-color-bone-muted);
}

.locale-switcher__btn:focus-visible {
    outline: 2px solid var(--rl-color-bone);
    outline-offset: -2px;
}
</style>
