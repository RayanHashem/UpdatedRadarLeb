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

        <button class="a-btn a-btn-default" @click="emit('close')">
            {{ t('common.back') }}
        </button>
    </div>
</template>
