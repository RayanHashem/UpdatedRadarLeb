<script setup lang="ts">
/*
 * Winners page shown over the Radar screen when the user taps the trophy
 * (2026 redesign "Winners" screen).
 *
 * Lists the latest real winners from GET /winners (WinnerController::index,
 * Laravel paginator JSON: { data: [{ id, game_name, user_name, created_at }] }).
 * Winner rows are added by admins in Filament; the date shown is the
 * winner row's created_at.
 */
import axios from 'axios'
import { onMounted, ref } from 'vue'
import RadarIcon from '@/components/dashboard/RadarIcon.vue'
import { useTranslate } from '@/composables/useTranslate'
import { prizeKey } from '@/lib/prizeNames'

type Winner = {
    id: number
    game_name: string | null
    user_name: string | null
    created_at: string | null
}

const emit = defineEmits<{
    (e: 'close'): void
}>()

const { t, locale } = useTranslate()

const status = ref<'loading' | 'ready' | 'error'>('loading')
const winners = ref<Winner[]>([])

async function load() {
    status.value = 'loading'
    try {
        const { data } = await axios.get('/winners', { params: { per_page: 20 } })
        winners.value = Array.isArray(data?.data) ? data.data : []
        status.value = 'ready'
    } catch {
        status.value = 'error'
    }
}

onMounted(load)

/** Translated prize name ("SUV" / "سيارة دفع رباعي"); unknown names show as stored. */
function prizeName(name: string | null): string {
    const key = prizeKey(name)
    return key ? t(`dashboard.radar.prize.${key}`) : (name ?? '')
}

/** "28 Sep 2026" / "28 أيلول 2026" (Western digits, like the rest of the app). */
function drawDate(iso: string | null): string {
    if (!iso) return ''
    const date = new Date(iso)
    if (Number.isNaN(date.getTime())) return ''
    if (locale.value === 'ar') {
        return new Intl.DateTimeFormat('ar-LB', { day: 'numeric', month: 'long', year: 'numeric', numberingSystem: 'latn' }).format(date)
    }
    const parts = new Intl.DateTimeFormat('en-US', { day: 'numeric', month: 'short', year: 'numeric' }).formatToParts(date)
    const part = (type: string) => parts.find((p) => p.type === type)?.value ?? ''
    return `${part('day')} ${part('month')} ${part('year')}`
}
</script>

<template>
    <div class="rl-sheet" role="dialog" aria-modal="true" aria-labelledby="winners-title">
        <div class="rl-sheet__column">
            <div class="rl-sheet__bar">
                <span class="rl-sheet__brand">Radar Leb</span>
                <button type="button" class="rl-sheet__close" :aria-label="t('dashboard.popup.close')" @click="emit('close')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <h1 id="winners-title" class="rl-title rl-sheet__title">{{ t('dashboard.winners.title') }}</h1>

            <template v-if="status === 'loading'">
                <p class="visually-hidden" role="status">{{ t('dashboard.winners.loading') }}</p>
                <ol class="winners__list" aria-hidden="true">
                    <li v-for="n in 3" :key="n" class="winners__card winners__card--skeleton">
                        <div class="winners__icon"></div>
                        <div class="winners__details">
                            <span class="winners__bone winners__bone--name"></span>
                            <span class="winners__bone winners__bone--meta"></span>
                        </div>
                    </li>
                </ol>
            </template>

            <div v-else-if="status === 'error'" class="winners__empty" role="alert">
                <p class="winners__empty-text">{{ t('dashboard.winners.error') }}</p>
                <button type="button" class="rl-button rl-button--secondary winners__retry" @click="load">
                    {{ t('dashboard.winners.retry') }}
                </button>
            </div>

            <div v-else-if="winners.length === 0" class="winners__empty">
                <div class="winners__icon winners__icon--muted" aria-hidden="true">
                    <RadarIcon name="trophy" :size="28" />
                </div>
                <p class="winners__empty-text">{{ t('dashboard.winners.empty') }}</p>
            </div>

            <ol v-else class="winners__list">
                <li
                    v-for="(winner, i) in winners"
                    :key="winner.id"
                    class="winners__card"
                    :style="{ animationDelay: Math.min(i, 8) * 60 + 'ms' }"
                >
                    <div class="winners__icon" aria-hidden="true">
                        <RadarIcon name="trophy" :size="24" />
                    </div>
                    <div class="winners__details">
                        <!-- <bdi>: names in either script keep their letter order but follow the page's alignment. -->
                        <span class="winners__name"><bdi>{{ winner.user_name || '—' }}</bdi></span>
                        <span class="winners__meta">{{ drawDate(winner.created_at) }} · {{ prizeName(winner.game_name) }}</span>
                    </div>
                </li>
            </ol>

            <div class="rl-sheet__spacer"></div>
            <button type="button" class="rl-button rl-button--secondary rl-sheet__back" @click="emit('close')">
                {{ t('dashboard.winners.back') }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.winners__list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.winners__card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: 16px;
    background: var(--rl-color-night-raised);
    animation: winners-in 260ms ease-out both;
}

.winners__icon {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--rl-color-signal-wash);
    color: var(--rl-color-alert);
}

.winners__details {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}

.winners__name {
    overflow: hidden;
    font-size: 17px;
    font-weight: 600;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.winners__meta {
    font-family: var(--rl-font-mono);
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--rl-color-bone-muted);
}

/* Loading placeholders: same card shape, shimmering bars. */
.winners__card--skeleton {
    animation: none;
}

.winners__card--skeleton .winners__icon {
    background: var(--rl-color-night-edge);
}

.winners__bone {
    display: block;
    height: 12px;
    border-radius: 6px;
    background: var(--rl-color-night-edge);
    animation: winners-shimmer 1.2s ease-in-out infinite alternate;
}

.winners__bone--name { width: 140px; height: 16px; }
.winners__bone--meta { width: 110px; }

.winners__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    padding: 32px 16px;
    border: 1px dashed var(--rl-color-slot-line);
    border-radius: 16px;
    text-align: center;
}

.winners__icon--muted {
    background: var(--rl-color-night-sunken);
    color: var(--rl-color-bone-subtle);
}

.winners__empty-text {
    margin: 0;
    font-size: 17px;
    font-weight: 600;
    line-height: 1.4;
}

.winners__retry {
    width: 100%;
}

/* Arabic: sans instead of mono, no tracking. */
.rl-sheet:lang(ar) .winners__meta {
    font-family: var(--rl-font-sans);
    font-size: 13px;
    letter-spacing: 0;
}

@keyframes winners-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}

@keyframes winners-shimmer {
    from { opacity: 0.45; }
    to { opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
    .winners__card,
    .winners__bone {
        animation: none;
    }
}
</style>
