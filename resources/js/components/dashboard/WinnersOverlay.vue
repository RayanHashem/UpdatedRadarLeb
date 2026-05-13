<script setup lang="ts">
/*
 * Winners overlay shown on top of the dashboard when the user taps the
 * trophy icon.
 *
 * TEMPORARY: the three winner cards below are hardcoded sample data
 * (USER.NAME01 / USER.NAME02 / USER.NAME03). The real `/winners` endpoint
 * exists (auth-gated, paginated) but isn't wired into this overlay yet.
 * Wiring is a small fetch on mount + a v-for over the result.
 *
 * See:
 *   - GET /winners (routes/web.php)
 *   - WinnerController::index returns Laravel paginator JSON
 *
 * To wire up, replace the static markup with something like:
 *   const winners = ref([])
 *   onMounted(async () => {
 *     const { data } = await axios.get('/winners?per_page=10')
 *     winners.value = data.data
 *   })
 *
 * For now we keep the placeholders so the visual flow is unchanged from the
 * previous implementation.
 */
import { useTranslate } from '@/composables/useTranslate'

const emit = defineEmits<{
    (e: 'close'): void
}>()

const { t } = useTranslate()
</script>

<template>
    <div class="winners-container">
        <h1 class="overlay-title">{{ t('dashboard.winners.title') }}</h1>

        <div class="winner-card">
            <div class="winner-icon">
                <img src="/assets/imgs/winner.png" alt="Winner Icon">
            </div>
            <div class="winner-details">
                <span class="winner-name">USER.NAME01</span>
                <span class="winner-prize">WINNER DRAW 1 - BIKE</span>
            </div>
        </div>
        <div class="winner-card">
            <div class="winner-icon">
                <img src="/assets/imgs/winner.png" alt="Winner Icon">
            </div>
            <div class="winner-details">
                <span class="winner-name">USER.NAME02</span>
                <span class="winner-prize">WINNER DRAW 1 - SUV</span>
            </div>
        </div>
        <div class="winner-card">
            <div class="winner-icon">
                <img src="/assets/imgs/winner.png" alt="Winner Icon">
            </div>
            <div class="winner-details">
                <span class="winner-name">USER.NAME03</span>
                <span class="winner-prize">WINNER DRAW 1 - Super Car</span>
            </div>
        </div>

        <!--
          Explicit close button — on small phones the overlay fills ~90% of
          the screen, leaving ≤18px of outside tap area to dismiss via
          @click.self. A visible Back button is mandatory for mobile.
        -->
        <button class="a-btn a-btn-default overlay-back-btn" @click="emit('close')">
            {{ t('common.back') }}
        </button>
    </div>
</template>
