<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="game-modal-overlay"
            @click.self="$emit('close')"
        >
            <!--
              2026 redesign popup card. Variants: 'antenna' (the "Antenna detected"
              screen: icon + 6-segment progress) and 'win' (the "You won" screen:
              logo, kicker line, red border). Every other popup (min deposit,
              location, ...) uses the plain card.
            -->
            <div
                class="game-modal-content"
                :class="{
                    'game-modal-content--antenna': variant === 'antenna',
                    'game-modal-content--win': variant === 'win',
                }"
                role="dialog"
                aria-modal="true"
                aria-labelledby="game-modal-title"
                :dir="dir"
                :lang="locale"
            >
                <div v-if="variant === 'antenna'" class="game-modal-icon" aria-hidden="true">
                    <RadarIcon name="antenna" :size="34" :stroke="1.6" />
                </div>
                <img
                    v-if="variant === 'win'"
                    src="/assets/imgs/radarleb-logo.png"
                    class="game-modal-logo"
                    width="128"
                    height="128"
                    alt=""
                />
                <span v-if="kicker" class="game-modal-kicker">{{ kicker }}</span>
                <h2 id="game-modal-title" class="rl-title game-modal-title">{{ title }}</h2>
                <div v-if="variant === 'antenna'" class="game-modal-progress" aria-hidden="true">
                    <span
                        v-for="n in 6"
                        :key="n"
                        class="game-modal-progress__seg"
                        :class="{ 'game-modal-progress__seg--on': n <= progress }"
                    ></span>
                </div>
                <p class="game-modal-message">{{ message }}</p>
                <p v-if="subtext" class="game-modal-subtext">{{ subtext }}</p>
                <div class="game-modal-actions">
                    <template v-if="primaryLabel">
                        <a
                            v-if="primaryRoute"
                            :href="primaryRoute"
                            :target="primaryNewTab ? '_blank' : undefined"
                            :rel="primaryNewTab ? 'noopener' : undefined"
                            class="rl-button game-modal-btn"
                            @click="$emit('close')"
                        >
                            {{ primaryLabel }}
                        </a>
                        <button
                            v-else
                            type="button"
                            class="rl-button game-modal-btn"
                            @click="onPrimaryClick"
                        >
                            {{ primaryLabel }}
                        </button>
                    </template>
                    <!-- With no primary action, the close button is the popup's main (red) button. -->
                    <button
                        type="button"
                        class="rl-button game-modal-btn"
                        :class="{ 'rl-button--secondary': !!primaryLabel }"
                        @click="$emit('close')"
                    >
                        {{ secondaryLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import RadarIcon from '@/components/dashboard/RadarIcon.vue';
import { useDirection, useTranslate } from '@/composables/useTranslate';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    message: { type: String, default: '' },
    subtext: { type: String, default: '' },
    primaryLabel: { type: String, default: '' },
    primaryRoute: { type: String, default: '' },
    primaryNewTab: { type: Boolean, default: false },
    primaryAction: { type: String, default: '' },
    secondaryLabel: { type: String, default: 'Close' },
    // Display only: 'antenna' shows the antenna icon and `progress` of 6 segments;
    // 'win' shows the logo and red border. `kicker` is the small line above the title.
    variant: { type: String, default: '' },
    progress: { type: Number, default: 0 },
    kicker: { type: String, default: '' },
});

const emit = defineEmits(['close', 'primary']);

// Teleported to <body>, so it can't inherit the Radar screen's dir/lang.
const { locale } = useTranslate();
const dir = useDirection();

function onPrimaryClick() {
    emit('primary', props.primaryAction);
    emit('close');
}
</script>

<style scoped>
.game-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: var(--rl-color-dialog-backdrop);
    animation: game-modal-fade 160ms ease-out;
}

.game-modal-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    box-sizing: border-box;
    width: 100%;
    max-width: 400px;
    max-height: calc(100svh - 48px);
    overflow-y: auto;
    padding: 28px 22px 22px;
    border: 1px solid var(--rl-color-dialog-edge);
    border-radius: 22px;
    background: var(--rl-color-night-raised);
    color: var(--rl-color-bone);
    font-family: var(--rl-font-sans);
    text-align: center;
    animation: game-modal-pop 220ms cubic-bezier(0.2, 0.9, 0.3, 1.2);
}

.game-modal-icon {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    width: 72px;
    height: 72px;
    border: 1.5px solid var(--rl-color-found);
    border-radius: 50%;
    background: var(--rl-color-found-wash);
    color: var(--rl-color-found-bright);
    animation: game-modal-ping 1.6s ease-out 1;
}

.game-modal-title {
    font-size: 30px;
}

.game-modal-content--antenna .game-modal-title {
    font-size: 38px;
}

/* You won: red border, logo, green kicker, big title. */
.game-modal-content--win {
    gap: 12px;
    padding-top: 26px;
    border: 1.5px solid var(--rl-color-signal);
    animation: game-modal-pop 220ms cubic-bezier(0.2, 0.9, 0.3, 1.2), game-modal-glow 1.8s ease-out 220ms 2;
}

.game-modal-logo {
    display: block;
    width: 128px;
    height: 128px;
    animation: game-modal-logo 520ms cubic-bezier(0.2, 0.9, 0.3, 1.35);
}

.game-modal-kicker {
    font-family: var(--rl-font-mono);
    font-size: 12px;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--rl-color-found-bright);
}

.game-modal-content--win .game-modal-title {
    font-size: 54px;
    line-height: 0.95;
}

.game-modal-content--win .game-modal-message {
    font-size: 17px;
    font-weight: 500;
    line-height: 1.45;
    color: var(--rl-color-bone);
}

.game-modal-content--win .game-modal-actions {
    margin-top: 8px;
}

.game-modal-content--win .game-modal-btn {
    font-size: 22px;
    letter-spacing: 0.12em;
}

.game-modal-progress {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 6px;
    width: 100%;
}

.game-modal-progress__seg {
    height: 8px;
    border-radius: 4px;
    background: var(--rl-color-dialog-edge);
}

.game-modal-progress__seg--on {
    background: var(--rl-color-found);
}

.game-modal-message {
    margin: 0;
    font-size: 15px;
    line-height: 1.5;
    color: var(--rl-color-dialog-text);
}

/* In the antenna popup the message is the "3 of 6 antennas found" count line. */
.game-modal-content--antenna .game-modal-message {
    font-family: var(--rl-font-mono);
    font-size: 14px;
    color: var(--rl-color-bone);
}

.game-modal-subtext {
    margin: 0;
    font-size: 15px;
    line-height: 1.5;
    color: var(--rl-color-dialog-text);
}

.game-modal-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
    margin-top: 6px;
}

.game-modal-btn {
    width: 100%;
    height: 54px;
    font-size: 21px;
    letter-spacing: 0.1em;
}

.game-modal-btn.rl-button--secondary {
    height: 52px;
    font-size: 20px;
}

/* Arabic: no tracking (it breaks letter joins) and no mono face for the count line. */
.game-modal-content:lang(ar) .game-modal-btn {
    letter-spacing: 0;
}

.game-modal-content:lang(ar) .game-modal-message,
.game-modal-content:lang(ar) .game-modal-kicker {
    font-family: var(--rl-font-sans);
    letter-spacing: 0;
}

.game-modal-content--win:lang(ar) .game-modal-title {
    line-height: 1.2;
}

@keyframes game-modal-fade {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes game-modal-pop {
    from { opacity: 0; transform: scale(0.94); }
    to { opacity: 1; transform: scale(1); }
}

@keyframes game-modal-glow {
    0% { box-shadow: 0 0 0 0 var(--rl-color-signal-glow); }
    100% { box-shadow: 0 0 0 16px transparent; }
}

@keyframes game-modal-logo {
    from { transform: scale(0.7) rotate(-8deg); opacity: 0; }
    to { transform: scale(1) rotate(0); opacity: 1; }
}

@keyframes game-modal-ping {
    0% { box-shadow: 0 0 0 0 var(--rl-color-found-glow); }
    100% { box-shadow: 0 0 0 18px transparent; }
}

@media (prefers-reduced-motion: reduce) {
    .game-modal-overlay,
    .game-modal-content,
    .game-modal-content--win,
    .game-modal-logo,
    .game-modal-icon {
        animation: none;
    }
}
</style>
