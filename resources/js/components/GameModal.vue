<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="game-modal-overlay"
            @click.self="$emit('close')"
        >
            <div class="game-modal-blur"></div>
            <div class="game-modal-content">
                <h2 class="game-modal-title">{{ title }}</h2>
                <p class="game-modal-message">{{ message }}</p>
                <p v-if="subtext" class="game-modal-subtext">{{ subtext }}</p>
                <div class="game-modal-actions">
                    <template v-if="primaryLabel">
                        <a
                            v-if="primaryRoute"
                            :href="primaryRoute"
                            class="a-btn a-btn-music-on game-modal-btn"
                        >
                            {{ primaryLabel }}
                        </a>
                        <button
                            v-else
                            type="button"
                            class="a-btn a-btn-music-on game-modal-btn"
                            @click="onPrimaryClick"
                        >
                            {{ primaryLabel }}
                        </button>
                    </template>
                    <button
                        type="button"
                        class="a-btn a-btn-default game-modal-btn"
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
const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    message: { type: String, default: '' },
    subtext: { type: String, default: '' },
    primaryLabel: { type: String, default: '' },
    primaryRoute: { type: String, default: '' },
    primaryAction: { type: String, default: '' },
    secondaryLabel: { type: String, default: 'Close' },
});

const emit = defineEmits(['close', 'primary']);

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
    padding: 1rem;
    box-sizing: border-box;
}
@media (max-width: 360px) {
    .game-modal-overlay { padding: 0.5rem; }
    .game-modal-content { padding: 1rem 1.25rem; max-width: 100%; }
    .game-modal-title { font-size: 1.1rem; }
    .game-modal-message, .game-modal-subtext { font-size: 0.9rem; }
    .game-modal-actions { flex-direction: column; gap: 0.5rem; }
    .game-modal-btn { width: 100%; text-align: center; }
}

.game-modal-blur {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.game-modal-content {
    position: relative;
    background: #04333f;
    color: #fff;
    border-radius: 16px;
    padding: 1.5rem 2rem;
    max-width: 400px;
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(93, 176, 161, 0.4);
}

.game-modal-title {
    margin: 0 0 0.75rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: #5DB0A1;
}

.game-modal-message {
    margin: 0 0 0.5rem;
    font-size: 0.95rem;
    line-height: 1.4;
    color: rgba(255, 255, 255, 0.95);
}

.game-modal-subtext {
    margin: 0 0 1.25rem;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
}

.game-modal-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1.25rem;
}

.game-modal-btn {
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
    border-radius: 12px;
    padding: 0.6rem 1.25rem;
    font-weight: 600;
    transition: opacity 0.2s ease;
}
.game-modal-btn:hover {
    opacity: 0.9;
}
.a-btn-music-on.game-modal-btn {
    background: #5DB0A1;
    color: #04333f;
    border: none;
}
.a-btn-default.game-modal-btn {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.25);
}
</style>
