<script setup lang="ts">
/*
 * Help overlay shown on top of the dashboard when the user taps the "?".
 *
 * All copy comes from lang/<locale>.json via useTranslate(). Step 2 contains
 * the support phone via `{phone}` interpolation — the value lives in
 * config/contact.php and is shared on every Inertia request as
 * `contact.phone`. `:dir` flips on Arabic so paragraphs flow correctly.
 *
 * Pure presentation: no fetches, no mutations. Emits `close` when the user
 * dismisses; the parent decides what that means.
 */
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTranslate, useDirection } from '@/composables/useTranslate'

const emit = defineEmits<{
    (e: 'close'): void
}>()

const { t } = useTranslate()
const dir   = useDirection()
const supportPhone = computed(() => usePage().props.contact?.phone ?? '')
</script>

<template>
    <div class="help-container" :dir="dir">
        <h1 class="overlay-title">{{ t('dashboard.help.title') }}</h1>
        <p>{{ t('dashboard.help.intro') }}</p>
        <h2 class="overlay-subtitle">{{ t('dashboard.help.howToTitle') }}</h2>
        <ol>
            <li>{{ t('dashboard.help.step1') }}</li>
            <li>{{ t('dashboard.help.step2', { phone: supportPhone }) }}</li>
            <li>{{ t('dashboard.help.step3') }}</li>
            <li>{{ t('dashboard.help.step4') }}</li>
            <li>{{ t('dashboard.help.step5') }}</li>
            <li>{{ t('dashboard.help.step6') }}</li>
        </ol>
        <p>{{ t('dashboard.help.notes') }}</p>

        <div class="help-tools" aria-label="Game tools">
            <div class="help-tool">
                <img src="/assets/imgs/my-location.png" alt="" class="help-tool__icon" />
                <div class="help-tool__copy">
                    <h3>{{ t('dashboard.help.locationTitle') }}</h3>
                    <p>{{ t('dashboard.help.locationText') }}</p>
                </div>
            </div>

            <div class="help-tool">
                <img src="/assets/imgs/radar-cash.png" alt="" class="help-tool__icon" />
                <div class="help-tool__copy">
                    <h3>{{ t('dashboard.help.radarCashTitle') }}</h3>
                    <p>{{ t('dashboard.help.radarCashText') }}</p>
                </div>
            </div>
        </div>

        <button class="a-btn a-btn-default" @click="emit('close')">
            {{ t('common.back') }}
        </button>
    </div>
</template>

<style scoped>
.help-tools {
    display: grid;
    gap: 0.75rem;
    margin: 1.25rem 0;
}

.help-tool {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    gap: 0.85rem;
    padding: 0.75rem;
    border-radius: 12px;
    background: rgba(98, 195, 255, 0.1);
    border: 1px solid rgba(98, 195, 255, 0.22);
    text-align: start;
}

.help-tool__icon {
    width: 48px;
    height: 48px;
    object-fit: contain;
}

.help-tool__copy h3 {
    margin: 0 0 0.2rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: #ffffff;
}

.help-tool__copy p {
    margin: 0;
    font-size: 0.85rem;
    line-height: 1.35;
    color: rgba(255, 255, 255, 0.86);
}

@media (max-width: 480px) {
    .help-tool {
        grid-template-columns: 38px minmax(0, 1fr);
        gap: 0.65rem;
        padding: 0.65rem;
    }

    .help-tool__icon {
        width: 38px;
        height: 38px;
    }

    .help-tool__copy h3 {
        font-size: 0.86rem;
    }

    .help-tool__copy p {
        font-size: 0.78rem;
    }
}
</style>
