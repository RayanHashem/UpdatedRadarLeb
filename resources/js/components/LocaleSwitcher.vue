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
.locale-switcher {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    user-select: none;
}

.locale-switcher__btn {
    appearance: none;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: transparent;
    color: inherit;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.04em;
    cursor: pointer;
    transition: background 120ms ease, border-color 120ms ease;
}

.locale-switcher__btn:hover {
    background: rgba(255, 255, 255, 0.08);
}

.locale-switcher__btn.is-active {
    background: rgba(255, 255, 255, 0.16);
    border-color: rgba(255, 255, 255, 0.4);
}
</style>
