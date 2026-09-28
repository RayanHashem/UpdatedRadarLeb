<script setup lang="ts">
/*
 * Settings page shown over the Radar screen (2026 redesign "Settings"
 * screen). Presentation only: Dashboard.vue owns the state and actions, so
 * Music still flips the same `audioEnabled` setting via toggleMusic(), and
 * Change password / Log out / Back do exactly what the old buttons did.
 */
import RadarIcon from '@/components/dashboard/RadarIcon.vue'
import { useTranslate } from '@/composables/useTranslate'

defineProps<{
    musicOn: boolean
}>()

const emit = defineEmits<{
    (e: 'toggle-music'): void
    (e: 'change-password'): void
    (e: 'logout'): void
    (e: 'close'): void
}>()

const { t } = useTranslate()
</script>

<template>
    <div class="rl-sheet" role="dialog" aria-modal="true" aria-labelledby="settings-title">
        <div class="rl-sheet__column">
            <div class="rl-sheet__bar">
                <span class="rl-sheet__brand">Radar Leb</span>
                <button type="button" class="rl-sheet__close" :aria-label="t('dashboard.popup.close')" @click="emit('close')">
                    <RadarIcon name="close" :size="20" :stroke="1.8" />
                </button>
            </div>

            <h1 id="settings-title" class="rl-title rl-sheet__title">{{ t('dashboard.radar.settings') }}</h1>

            <div class="settings__group">
                <button
                    type="button"
                    role="switch"
                    class="settings__row"
                    :aria-checked="musicOn ? 'true' : 'false'"
                    @click="emit('toggle-music')"
                >
                    <RadarIcon name="music" />
                    <span class="settings__label">{{ t('dashboard.settings.music') }}</span>
                    <span class="settings__switch" :class="{ 'settings__switch--on': musicOn }" aria-hidden="true">
                        <span class="settings__knob"></span>
                    </span>
                </button>
                <div class="settings__divider"></div>
                <button type="button" class="settings__row" @click="emit('change-password')">
                    <RadarIcon name="lock" />
                    <span class="settings__label">{{ t('dashboard.settings.changePassword') }}</span>
                    <RadarIcon name="chevron" :size="18" :stroke="1.8" class="settings__chevron" />
                </button>
            </div>

            <button type="button" class="settings__row settings__row--card settings__logout" @click="emit('logout')">
                <RadarIcon name="logout" class="settings__flip" />
                <span class="settings__label">{{ t('common.logout') }}</span>
            </button>

            <div class="rl-sheet__spacer"></div>
            <button type="button" class="rl-button rl-button--secondary rl-sheet__back" @click="emit('close')">
                {{ t('common.back') }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.settings__group {
    overflow: hidden;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: 16px;
    background: var(--rl-color-night-raised);
}

.settings__row {
    display: flex;
    align-items: center;
    gap: 14px;
    box-sizing: border-box;
    width: 100%;
    height: 64px;
    padding: 0 16px;
    border: 0;
    background: transparent;
    color: var(--rl-color-bone);
    font: inherit;
    text-align: start;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    transition: background-color 220ms ease-out;
}

.settings__row:active {
    background: var(--rl-color-press-wash);
    transition-duration: 60ms;
}

.settings__row:focus-visible {
    outline: 2px solid var(--rl-color-bone);
    outline-offset: -2px;
}

.settings__label {
    flex-grow: 1;
    font-size: 17px;
    font-weight: 500;
}

/* Divider starts under the labels (16px padding + 22px icon + 14px gap). */
.settings__divider {
    height: 1px;
    margin-inline-start: 52px;
    background: var(--rl-color-night-edge);
}

.settings__chevron {
    flex-shrink: 0;
    color: var(--rl-color-dish-label);
}

.settings__row--card {
    margin-top: 12px;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: 16px;
    background: var(--rl-color-night-raised);
}

.settings__logout {
    color: var(--rl-color-alert);
}

/* Music switch: the knob slides and the track turns green when music is on. */
.settings__switch {
    position: relative;
    flex-shrink: 0;
    width: 50px;
    height: 30px;
    border-radius: 15px;
    background: var(--rl-color-night-line);
    transition: background-color 200ms ease;
}

.settings__switch--on {
    background: var(--rl-color-found);
}

.settings__knob {
    position: absolute;
    top: 3px;
    inset-inline-start: 3px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--rl-color-bone);
    transition: transform 200ms cubic-bezier(0.3, 0.7, 0.4, 1.3);
}

.settings__switch--on .settings__knob {
    transform: translateX(20px);
}

/* Right-to-left: knob travels the other way; chevron and exit arrow point the other way. */
[dir='rtl'] .settings__switch--on .settings__knob {
    transform: translateX(-20px);
}

[dir='rtl'] .settings__chevron,
[dir='rtl'] .settings__flip {
    transform: scaleX(-1);
}

@media (prefers-reduced-motion: reduce) {
    .settings__switch,
    .settings__knob {
        transition: none;
    }
}
</style>
