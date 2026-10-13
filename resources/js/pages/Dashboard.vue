<template>
    <!--
      Bungee + preconnect <link> tags moved to resources/views/app.blade.php
      so they're declared once per document load instead of once per Inertia
      visit. See the comment in app.blade.php near the @vite directive.

      The locale switcher used to render here as `position: fixed; top: 12px;
      inset-inline-end: 12px;` — that put it right where the new top-bar
      labeled icons sit, so EN/AR was overlapping LOGOUT. It now lives inside
      `.bar-right` (see the top bar below), so it flows with the layout.
    -->

    <div>
        <!--
          Desktop gate. When the user opens the game on a laptop or
          desktop the radar makes no sense (no movement, no location),
          so we cover the page with the DesktopBlock overlay and exit
          early from onMounted so none of the video/audio/scan setup
          runs. Admin (/admin) is a separate app and is unaffected.
        -->
        <DesktopBlock v-if="isDesktop" />

        <!--
          In-game pages (2026 redesign) are full-screen sheets (.rl-sheet in
          resources/css/redesign.css) with their own Close / Back buttons.
        -->
        <WinnersOverlay v-if="activeOverlay === 'winners'" @close="activeOverlay = null" />
        <HelpOverlay v-else-if="activeOverlay === 'help'" @close="activeOverlay = null" />
        <!-- Same handlers the old Settings buttons used (Music / Change password / Log out / Back). -->
        <SettingsOverlay
            v-else-if="activeOverlay === 'settings' && currentPage === 'settings'"
            :music-on="audioEnabled"
            @toggle-music="toggleMusic"
            @change-password="currentPage = 'password'"
            @logout="handleLogout"
            @close="activeOverlay = null"
        />

        <!-- Same flow and handlers as before: verify old password → set new one; Back returns to Settings. -->
        <ChangePasswordSheet
            v-else-if="activeOverlay === 'settings' && currentPage === 'password'"
            v-model:old-password="oldPassword"
            v-model:new-password="newPassword"
            v-model:confirm-password="confirmPassword"
            :verified="oldPasswordVerified"
            :wrong-attempts="wrongAttempts"
            :verifying="verifyingPassword"
            :updating="updatingPassword"
            :success="showSuccessMessage"
            :password-error="passwordError"
            :new-password-error="newPasswordError"
            :confirm-password-error="confirmPasswordError"
            @verify="verifyOldPassword"
            @update="updatePassword"
            @back="resetPasswordFlow"
        />


        <LoadingScreen v-if="loading" />

        <section v-show="!loading" id="game">
            <!--
              2026 redesign "Radar" screen. Every control keeps its original handler
              (openOverlay, onPrizeSlotClick, startScan, onLocationTap, LocaleSwitcher)
              and every number comes from the same state as before (walletBalance,
              selectedGameId, currentProgress.radar_level, visibleCount, radarOnline).
              One addition: the Radar Cash tile now opens How to play.

              No <section> elements inside: app.css forces every section to
              100svh / overflow:hidden.
            -->
            <!-- The no-op touchstart listener lets iOS Safari show the :active press states below. -->
            <div class="radar-screen" :dir="dir" :lang="locale" @touchstart.passive="() => {}">
                <header class="radar-screen__bar">
                    <div class="radar-screen__brand">
                        <img src="/assets/imgs/radarleb-icon.png" class="radar-screen__brand-icon" width="30" height="30" alt="" />
                        <span class="radar-screen__brand-name">Radar Leb</span>
                    </div>
                    <nav class="radar-screen__nav" :aria-label="t('dashboard.radar.menu')">
                        <LocaleSwitcher class="radar-screen__locale" />
                        <button type="button" class="radar-screen__icon-btn" :aria-label="t('dashboard.radar.winners')" @click="openOverlay('winners')">
                            <RadarIcon name="trophy" />
                        </button>
                        <button type="button" class="radar-screen__icon-btn" :aria-label="t('dashboard.radar.help')" @click="openOverlay('help')">
                            <RadarIcon name="help" />
                        </button>
                        <button type="button" class="radar-screen__icon-btn" :aria-label="t('dashboard.radar.settings')" @click="openOverlay('settings')">
                            <RadarIcon name="settings" />
                        </button>
                    </nav>
                </header>

                <!--
                  Radar map (2026 "Radar map" screen). One region lights up per antenna
                  found, in a per-player, per-prize order (lib/radarRegions.ts), so the
                  map is the same after a reload. The status cards and the "Antenna
                  found" note sit on top of the map.
                -->
                <RadarMap class="radar-map-card" :found="foundRegions" :fresh="freshRegion" :scanning="scanning">
                    <div class="radar-status" role="group" :aria-label="t('dashboard.radar.status')">
                        <div class="radar-card">
                            <span class="radar-label">{{ t('dashboard.radar.targetPrize') }}</span>
                            <span class="radar-card__value">{{ selectedPrizeTile ? t(`dashboard.radar.prize.${selectedPrizeTile.key}`) : t('dashboard.radar.none') }}</span>
                            <span class="radar-card__meta">{{ selectedPrizeTile ? selectedPrizeTile.price : t('dashboard.radar.pickPrize') }}</span>
                        </div>
                        <div class="radar-status__side">
                            <div class="radar-card radar-card--radar">
                                <span class="radar-label">{{ t('dashboard.radar.radar') }}</span>
                                <span class="radar-card__status">
                                    <span class="radar-card__dot" :class="radarOnline ? 'radar-card__dot--live' : 'radar-card__dot--offline'"></span>
                                    {{ scanning ? t('dashboard.radar.scanning') : radarOnline ? t('dashboard.radar.online') : t('dashboard.radar.offline') }}
                                </span>
                                <!--
                                  Idle: two bars lit and pulsing. During a scan they fill with the same
                                  8-step counter (visibleCount, 16 s) that used to fill the side bar.
                                -->
                                <div
                                    class="radar-card__bars"
                                    role="img"
                                    :aria-label="visibleCount > 0 ? t('dashboard.radar.scanProgress', { done: visibleCount, total: originalColors.length }) : t('dashboard.radar.signal')"
                                >
                                    <span
                                        v-for="n in originalColors.length"
                                        :key="n"
                                        class="radar-card__bar"
                                        :class="{ 'radar-card__bar--on': visibleCount > 0 ? n <= visibleCount : n <= 2 }"
                                        :style="{ height: 3 + (n - 1) * 1.5 + 'px', animationDelay: (n - 1) * 0.12 + 's' }"
                                    ></span>
                                </div>
                            </div>
                            <span class="radar-north" aria-hidden="true">
                                <svg width="12" height="16" viewBox="0 0 12 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 15V2" />
                                    <path d="M2 6l4-4 4 4" />
                                </svg>
                                {{ t('dashboard.radar.north') }}
                            </span>
                        </div>
                    </div>
                    <div v-if="mapNote" class="radar-note" role="status">
                        <span class="radar-note__dot"></span>
                        <span>{{ mapNote }}</span>
                    </div>
                </RadarMap>

                <div class="antenna-panel" role="group" :aria-label="t('dashboard.radar.antennaDetection')">
                    <div class="antenna-panel__head">
                        <span class="radar-label">{{ t('dashboard.radar.antennaDetection') }}</span>
                        <span class="antenna-panel__count" dir="ltr">{{ currentProgress.radar_level }} / 6</span>
                    </div>
                    <div class="antenna-panel__slots">
                        <div
                            v-for="n in 6"
                            :key="n"
                            class="antenna-slot"
                            :class="{ 'antenna-slot--found': n <= currentProgress.radar_level }"
                        >
                            <RadarIcon name="antenna" :size="20" />
                            <!-- The region this antenna lit on the map; a dash until it's found. -->
                            <span v-if="foundRegions[n - 1]" class="antenna-slot__name">{{ t(`dashboard.radar.region.${foundRegions[n - 1]}.short`) }}</span>
                            <span v-else class="antenna-slot__name" aria-hidden="true">—</span>
                        </div>
                    </div>
                </div>

                <div class="prize-picker" :class="{ 'prize-picker--prompt': !selectedPrizeTile }" role="group" :aria-label="t('dashboard.radar.choosePrize')">
                    <div class="radar-label prize-picker__title">{{ t('dashboard.radar.choosePrize') }}</div>
                    <div class="prize-picker__grid">
                        <div
                            v-for="(config, index) in PRIZE_DISPLAY_CONFIG"
                            :key="index"
                            class="prize-tile"
                            :style="{ '--tile-index': index }"
                            :class="{
                                'prize-tile--selected':
                                    orderedPrizes[index] && selectedGameId === orderedPrizes[index].id,
                            }"
                            role="button"
                            tabindex="0"
                            :aria-label="`${t(`dashboard.radar.prize.${PRIZE_TILES[index].key}`)}, ${PRIZE_TILES[index].price}`"
                            :aria-pressed="!!(orderedPrizes[index] && selectedGameId === orderedPrizes[index].id)"
                            @click="onPrizeSlotClick(index)"
                            @keydown.enter.prevent="onPrizeSlotClick(index)"
                            @keydown.space.prevent="onPrizeSlotClick(index)"
                        >
                            <RadarIcon :name="PRIZE_TILES[index].icon" :size="24" :stroke="1.6" />
                            <span class="prize-tile__name">{{ t(`dashboard.radar.prize.${PRIZE_TILES[index].key}.short`) }}</span>
                            <span class="prize-tile__price" dir="ltr">{{ PRIZE_TILES[index].priceShort }}</span>
                        </div>
                    </div>
                </div>

                <div class="radar-actions">
                    <div class="radar-actions__side">
                        <!-- Opens How to play (it explains topping up), like the Minimum deposit popup's button. -->
                        <button
                            type="button"
                            class="radar-actions__tile"
                            :aria-label="t('dashboard.radar.radarCashAria', { balance: walletBalance })"
                            @click="openOverlay('help')"
                        >
                            <RadarIcon name="wallet" />
                            <span class="radar-actions__balance">{{ walletBalance }}</span>
                        </button>
                        <span class="radar-actions__label">{{ t('dashboard.radar.radarCash') }}</span>
                    </div>
                    <button
                        id="scan"
                        class="radar-scan"
                        :class="{
                            'radar-scan--busy': scanning,
                            'radar-scan--long': !missedScan && buttonText.length > (locale === 'ar' ? 11 : 9),
                            'radar-scan--missed': missedScan,
                        }"
                        :disabled="!canScan" :style="buttonStyle" @click="startScan"
                    >
                        <!-- Missed scan (2 s): "ANTENNA NOT DETECTED" with "Try again" underneath. -->
                        <template v-if="missedScan">
                            <span class="radar-scan__miss">{{ buttonText }}</span>
                            <span class="radar-scan__retry">{{ t('dashboard.radar.tryAgain') }}</span>
                        </template>
                        <template v-else>{{ buttonText }}</template>
                    </button>
                    <div class="radar-actions__side">
                        <!--
                          Previously had @touchstart.prevent AND @click.prevent. On iOS the
                          touchstart handler could fire before the synthetic click arrived
                          and .prevent on touchstart sometimes blocked the click entirely,
                          producing a "button didn't register" effect. onLocationTap is
                          already debounced, so a single @click handler is enough and plays
                          nicely with the mobile tap gesture.
                        -->
                        <button
                            type="button"
                            class="radar-actions__tile"
                            :aria-label="t('dashboard.radar.shareLocation')"
                            @click.prevent="onLocationTap"
                        >
                            <RadarIcon name="pin" :size="24" />
                        </button>
                        <span class="radar-actions__label">{{ t('dashboard.radar.myLocation') }}</span>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <GameModal
        :show="gameModalShow"
        :title="gameModalTitle"
        :message="gameModalMessage"
        :subtext="gameModalSubtext"
        :primary-label="gameModalPrimaryLabel"
        :primary-route="gameModalPrimaryRoute"
        :primary-new-tab="gameModalPrimaryNewTab"
        :primary-action="gameModalPrimaryAction"
        :secondary-label="gameModalSecondaryLabel"
        :variant="gameModalVariant"
        :progress="gameModalProgress"
        :kicker="gameModalKicker"
        @close="gameModalShow = false"
        @primary="onModalPrimary"
    />
    <audio id="scanSound" src="/assets/imgs/audio/radar.mp3" preload="auto"></audio>
    <audio id="scanSound2" src="/assets/imgs/audio/radar2.mp3" preload="auto"></audio>
    <audio id="hornSound" src="/assets/imgs/horn.mp3" preload="auto"></audio>
    <audio id="clickSound" src="/assets/imgs/click.mp3" preload="auto"></audio>
    <!-- Popup sound. Played by showGameModal(), so every modal gets it. -->
    <audio id="popupSound" src="/assets/imgs/audio/popup.mp3" preload="auto"></audio>
    <!--
      Win celebration sound. Drop the source file at
      `public/assets/imgs/audio/purge.mp3` (matches the path pattern of
      the other game audio). The element fails silently if missing, so
      shipping without the file just degrades to a silent congrats popup.
    -->
    <audio id="purgeSound" src="/assets/imgs/audio/purge.mp3" preload="auto"></audio>

    <!--
      Mobile "Add to Home Screen" prompt. Renders only on actual mobile
      devices (gated by the same isDesktop check the page uses) and only
      once per 30 days per device. Skipping on desktop avoids pestering
      laptop users who already see the <DesktopBlock /> overlay.
    -->
    <AddToHomeScreenPrompt v-if="!isDesktop" />
</template>
<script setup>
import { ref, onMounted, computed, watch, nextTick, onUnmounted } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import GameModal from '@/components/GameModal.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import HelpOverlay from '@/components/dashboard/HelpOverlay.vue';
import WinnersOverlay from '@/components/dashboard/WinnersOverlay.vue';
import SettingsOverlay from '@/components/dashboard/SettingsOverlay.vue';
import ChangePasswordSheet from '@/components/dashboard/ChangePasswordSheet.vue';
import LoadingScreen from '@/components/dashboard/LoadingScreen.vue';
import RadarIcon from '@/components/dashboard/RadarIcon.vue';
import RadarMap from '@/components/dashboard/RadarMap.vue';
import { regionOrder } from '@/lib/radarRegions';
import AddToHomeScreenPrompt from '@/components/AddToHomeScreenPrompt.vue';
import DesktopBlock from '@/components/DesktopBlock.vue';

/*
 * Mobile-only gate for the main game.
 *
 * We detect "mobile" as: a known mobile/touch UA OR an iPad reporting as
 * Mac but exposing more than one touch point (iPadOS 13+). Anything that
 * doesn't match — laptops, desktops, dev tools without touch emulation —
 * gets the <DesktopBlock /> overlay instead of the radar. Detection runs
 * once at <script setup> so the overlay paints on first render with no
 * flash of the game UI; resizing a desktop window narrow does NOT flip
 * the gate, since the constraint is physical (movement / location), not
 * viewport-driven.
 */
function detectMobileDevice() {
    if (typeof window === 'undefined' || typeof navigator === 'undefined') {
        return true;
    }
    const ua = navigator.userAgent || '';
    if (/iPhone|iPad|iPod|Android|webOS|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(ua)) {
        return true;
    }
    // iPadOS 13+ reports as Macintosh but has multi-touch.
    if (/Macintosh/i.test(ua) && (navigator.maxTouchPoints || 0) > 1) {
        return true;
    }
    return false;
}

const isDesktop = ref(!detectMobileDevice());
import { getPrizeRules, PRIZE_SLOT_RULES } from '@/lib/prizeRules.js';
import { useDirection, useTranslate } from '@/composables/useTranslate';

// i18n bindings — `t` is still used for the (still-inline) settings overlay
// and a few one-off strings on the page. The Help and Winners overlays now
// own their own translation lookups internally.
const { t, locale } = useTranslate();
const dir = useDirection();

const PRIZE_DISPLAY_ORDER = ['Mobile', 'Bike & Electronics', 'SUV', 'Muscle Car', 'Super Car'];

/** Display config for each prize slot (same order as orderedPrizes). Enables Vue @click instead of DOM listeners. */
const PRIZE_DISPLAY_CONFIG = [
    { img: '/assets/imgs/mobile1.png', detectedImg: '/assets/imgs/mobile-detected1.png', label: 'MOBILE', price: '2.000$' },
    { img: '/assets/imgs/be1.png', detectedImg: '/assets/imgs/be-detected1.png', label: 'BIKE / ELECTRONICS', price: '15.000$' },
    { img: '/assets/imgs/suv1.png', detectedImg: '/assets/imgs/suv-detected1.png', label: 'SUV', price: '50.000$' },
    { img: '/assets/imgs/muscle-car1.png', detectedImg: '/assets/imgs/muscle-car-detected1.png', label: 'MUSCLE CAR', price: '150.000$' },
    { img: '/assets/imgs/super-car1.png', detectedImg: '/assets/imgs/super-car-detected1.png', label: 'SUPER CAR', price: '200.000$' },
];

/**
 * 2026 redesign icon + price for each prize slot (same order as PRIZE_DISPLAY_CONFIG / orderedPrizes).
 * Names come from lang/*.json: dashboard.radar.prize.<key> (full) and .<key>.short (tile). Display only.
 */
const PRIZE_TILES = [
    { key: 'mobile', icon: 'mobile', price: '$2,000', priceShort: '$2K' },
    { key: 'bike', icon: 'bike', price: '$15,000', priceShort: '$15K' },
    { key: 'suv', icon: 'suv', price: '$50,000', priceShort: '$50K' },
    { key: 'muscle', icon: 'muscle', price: '$150,000', priceShort: '$150K' },
    { key: 'supercar', icon: 'supercar', price: '$200,000', priceShort: '$200K' },
];

const activeOverlay = ref(null);

const props = defineProps({
    games: { type: Array, default: () => [] },
    selectedGameId: { type: Number, default: null },
    wallet_balance: Number,
    showHelpOnLoad: { type: Boolean, default: false },
});

const prizes = ref(props.games);
const walletBalance = ref(Number(props.wallet_balance) || 0);

const selectedGameId = ref(props.selectedGameId ?? null);

const orderedPrizes = computed(() => {
    const names = ['Mobile', 'Bike & Electronics', 'SUV', 'Muscle Car'];
    const firstFour = names.map((name) => prizes.value.find((p) => p.name === name) ?? null).filter(Boolean);
    const superCar = prizes.value.find(
        (p) =>
            p.name === 'Super Car' ||
            p.name === 'Super Cash Prize' ||
            /super\s*(cash\s*prize|car)/i.test(String(p.name))
    );
    const others = prizes.value.filter((p) => !names.includes(p.name));
    const fifth = superCar ?? others[others.length - 1] ?? null;
    return [...firstFour, fifth].filter(Boolean);
});

const gameModalShow = ref(false);
const gameModalTitle = ref('');
const gameModalMessage = ref('');
const gameModalSubtext = ref('');
const gameModalPrimaryLabel = ref('');
const gameModalPrimaryRoute = ref('');
const gameModalPrimaryNewTab = ref(false);
const gameModalPrimaryAction = ref('');
const gameModalSecondaryLabel = ref('Close');
const gameModalVariant = ref('');
const gameModalProgress = ref(0);
const gameModalKicker = ref('');
const loading = ref(true);

const radarOnline = ref(true);
const scanning = ref(false);
const detectionStatus = ref('idle');
const visibleCount = ref(0);
const antennaIconSrc = ref('/assets/imgs/an.png');

// Ref for the radar video element
const radarVideo = ref(null);

/*
 * radar.webm is ~77MB. `php artisan serve` (single-threaded on Windows) will
 * stream that one file for a long time and starve every other asset request
 * (prize icons, logo, header buttons). We only mount the <source> tags after
 * the rest of the page has finished loading so icons appear instantly and the
 * videos stream in afterwards — behavior (autoplay + loop) is unchanged.
 */
const videoSrcsReady = ref(false);

const originalColors = [
    '#4AEBD5', '#7BE9DD', '#81D6C1',
    '#E97E7F', '#E77E7F', '#E95E72',
    '#E25669', '#E45A73',
];

const currentPage = ref('settings');
const audioEnabled = ref((() => { try { return localStorage.getItem("radarleb:audioEnabled") !== "0"; } catch { return true; } })());
const oldPassword = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const oldPasswordVerified = ref(false);
const wrongAttempts = ref(0);
const passwordError = ref('');
const newPasswordError = ref('');
const confirmPasswordError = ref('');
const verifyingPassword = ref(false);
const updatingPassword = ref(false);
const showSuccessMessage = ref(false);
const help = ref(false);
const userLocation = ref({ lat: null, lng: null });
const locationUrl = ref('https://www.google.com/maps?q=33.8938,35.5018'); // Default fallback location
/** ID from navigator.geolocation.watchPosition; cleared in onUnmounted. */
const locationWatchId = ref(null);
/** Prevents duplicate getCurrentPosition calls and duplicate permission prompts. */
const locationRequestInProgress = ref(false);
/** Dedupe touch + click so we only run once per tap (touchend fires first on mobile, then click may fire). */
let lastLocationTapAt = 0;
const LOCATION_TAP_DEBOUNCE_MS = 500;
let bgVideoResumeId = null;

const canScan = computed(() => radarOnline.value && !scanning.value && selectedGameEnabled.value);

/* The 2 s after a missed scan, while the button reads "Antenna not detected". */
const missedScan = computed(() => selectedGameEnabled.value && !scanning.value && detectionStatus.value === 'not-found');

const buttonText = computed(() => {
    if (!selectedGameEnabled.value) return t('dashboard.radar.comingSoon');
    if (scanning.value) return t('dashboard.radar.scanning');
    if (detectionStatus.value === 'found') return t('dashboard.radar.antennaDetected');
    if (detectionStatus.value === 'not-found') return t('dashboard.radar.antennaNotDetected');
    return t('dashboard.radar.scan');
});

const buttonStyle = computed(() => {
    if (scanning.value) {
        return { backgroundColor: 'var(--rl-color-signal-deep)', color: '#fff' };
    } else if (missedScan.value) {
        return {}; // .radar-scan--missed draws the "not detected" state
    } else {
        return { backgroundColor: 'var(--rl-color-signal)', color: '#fff' };
    }
});

const selectedGameEnabled = computed(() => {
    const g = prizes.value.find(p => p.id == selectedGameId.value);
    return g ? !!g.is_enabled : true; // default true if missing
});

const selectedPrize = computed(() =>
    prizes.value.find(p => p.id == selectedGameId.value)
);

function showGameModal(config) {
    gameModalTitle.value = config.title ?? '';
    gameModalMessage.value = config.message ?? '';
    gameModalSubtext.value = config.subtext ?? '';
    gameModalPrimaryLabel.value = config.primaryLabel ?? '';
    gameModalPrimaryRoute.value = config.primaryRoute ?? '';
    gameModalPrimaryNewTab.value = config.primaryNewTab ?? false;
    gameModalPrimaryAction.value = config.primaryAction ?? '';
    gameModalSecondaryLabel.value = config.secondaryLabel ?? t('dashboard.popup.close');
    gameModalVariant.value = config.variant ?? '';
    gameModalProgress.value = config.progress ?? 0;
    gameModalKicker.value = config.kicker ?? '';
    gameModalShow.value = true;
    playPopupSound();
}

/*
 * Prize-specific "minimum deposit" popup.
 *
 * A new user lands on the dashboard with $0 wallet and no prize selected.
 * The first things they'll tap are (a) a prize tile → this modal, or
 * (b) the Scan button → "Select a prize first" or minimum-deposit modals
 * in startScan(). Help opens the same top-bar overlay as the menu.
 */
/*
 * Win celebration popup + sound. Triggered from startScan() when the user's
 * radar reaches level 6 on a successful scan. The message is dynamic — it
 * includes the prize name so the same popup works for Mobile, Bike &
 * Electronics, SUV, Muscle Car, and Super Car. The "purge" sound is a
 * separate <audio id="purgeSound"> element in the template; the file at
 * `public/assets/imgs/audio/purge.mp3` is what feeds it. If the file
 * isn't deployed yet, the popup still shows but plays nothing.
 */
function celebrateWin(prizeName) {
    const label = prizeDisplayName(prizeName);
    const audio = document.getElementById('purgeSound');
    if (audio && audioEnabled.value) {
        try {
            audio.currentTime = 0;
            const p = audio.play();
            if (p && typeof p.catch === 'function') {
                p.catch(() => { /* autoplay blocked — celebration is silent */ });
            }
        } catch { /* no-op */ }
    }
    showGameModal({
        title: t('dashboard.popup.win.title'),
        message: label
            ? t('dashboard.popup.win.message', { prize: label })
            : t('dashboard.popup.win.messageNoPrize'),
        subtext: t('dashboard.popup.win.subtext'),
        primaryLabel: '',
        primaryAction: '',
        secondaryLabel: t('dashboard.popup.win.button'),
        variant: 'win',
        kicker: t('dashboard.popup.win.kicker'),
    });
}

/*
 * Per-antenna progress popup. Fires after every successful detection
 * EXCEPT the 6th (which gets the full celebrateWin popup instead). Mirrors
 * the radar_level the server just returned, so it always matches what the
 * user actually has — no client-side counter to drift out of sync.
 */
function showAntennaProgressModal(level, prizeName) {
    const label = prizeDisplayName(prizeName);
    const countLine = t('dashboard.popup.antenna.count', { count: level });
    const message = level === 5
        ? `${countLine} — ${t('dashboard.popup.antenna.almost')}`
        : countLine;
    showGameModal({
        title: t('dashboard.popup.antenna.title'),
        message,
        subtext: label
            ? t('dashboard.popup.antenna.keepPlaying', { prize: label })
            : t('dashboard.popup.antenna.keepPlayingNoPrize'),
        primaryLabel: '',
        primaryAction: '',
        secondaryLabel: t('dashboard.popup.antenna.continue'),
        variant: 'antenna',
        progress: level,
    });
}

function showMinDepositModal(message) {
    showGameModal({
        title: t('dashboard.popup.minDeposit.title'),
        message,
        primaryLabel: t('dashboard.popup.howToPlay'),
        primaryAction: 'openHelp',
        secondaryLabel: t('dashboard.popup.cancel'),
    });
}

/** Popup prize name in the current language ("Mobile" / "هاتف محمول"); falls back to the DB name. */
function prizeDisplayName(name) {
    const slot = orderedPrizes.value.findIndex(p => p && p.name === name);
    return slot === -1 ? (name ? String(name).trim() : '') : t(`dashboard.radar.prize.${PRIZE_TILES[slot].key}`);
}

/** Minimum-deposit copy in the current language; amounts come from prizeRules.js as before. */
function minDepositMessage(prizeName) {
    const rules = getPrizeRules(prizeName);
    return rules
        ? t('dashboard.popup.minDeposit.message', { amount: rules.minDepositDollars })
        : t('dashboard.popup.minDeposit.generic');
}

function minDepositMessageBySlot(index) {
    const slot = PRIZE_SLOT_RULES[index];
    return slot
        ? t('dashboard.popup.minDeposit.message', { amount: slot.minDepositDollars })
        : t('dashboard.popup.minDeposit.generic');
}

function onModalPrimary(action) {
    if (action === 'openHelp') {
        activeOverlay.value = 'help';
    }
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/*
 * Prize click flow — "instant" means the image swap paints on the SAME frame
 * as the click, not the frame after. Two subtle things matter:
 *
 *   1. We used to schedule playClickSound() with queueMicrotask, but
 *      microtasks run *before* the browser paints. On first interaction the
 *      mp3 decode can chew 20–60ms of that microtask, which pushes the
 *      paint back — visually indistinguishable from a "laggy button".
 *      Switching to setTimeout(…, 0) defers the audio to after paint, so
 *      the prize image always swaps first and the click sound follows.
 *
 *   2. The cheap synchronous guard (disabled / min deposit modal) still
 *      runs first. If the prize is playable we flip `selectedGameId` in
 *      the same tick so Vue's reactive DOM patch lands in the same frame.
 *      The /me/game POST is fire-and-forget; a rollback only happens on 403.
 */
function onPrizeSlotClick(index) {
    const prize = orderedPrizes.value[index];
    if (!prize) {
        setTimeout(playClickSound, 0);
        showMinDepositModal(minDepositMessageBySlot(index));
        return;
    }
    selectPrize(prize.id);
    setTimeout(playClickSound, 0);
}

function onPrizeItemClick(prize) {
    selectPrize(prize.id);
    setTimeout(playClickSound, 0);
}

function selectPrize(id) {
    const prize = prizes.value.find(p => p.id === id);
    if (!prize) return;

    /*
     * CRITICAL ORDER: the is_enabled (admin toggle in Filament → Prizes)
     * check MUST run before the min-deposit check. When an operator turns a
     * prize OFF in the admin panel every tap on that tile should surface the
     * exact same "Coming soon" modal — regardless of whether the user has
     * $0 or $1000 in their wallet. A disabled prize isn't a "minimum
     * deposit" problem, it's a "not available right now" problem, and
     * mixing those two messages would tell a well-funded user to deposit
     * more money for a prize that literally cannot be played.
     */
    if (!prize.is_enabled) {
        showGameModal({
            title: t('dashboard.popup.comingSoon.title'),
            message: t('dashboard.popup.comingSoon.message'),
            primaryLabel: '',
            primaryAction: '',
            secondaryLabel: t('dashboard.popup.close'),
        });
        return;
    }

    const rules = getPrizeRules(prize.name);
    const balance = Number(walletBalance.value) || 0;
    const scanCost = rules?.scanCostRadars ?? 0;

    if (balance < scanCost) {
        const message = minDepositMessage(prize.name);
        showMinDepositModal(message);
        return;
    }

    const previousId = selectedGameId.value;
    selectedGameId.value = id;

    /*
     * Server-side safety net: if the admin flipped the toggle OFF between
     * Inertia's initial page prop and this click (so our local copy of
     * prize.is_enabled is stale), /me/game returns 403 "Prize is disabled".
     * We roll back the optimistic selection and surface the same "Coming
     * soon" modal as the client-side branch above, so the user experience
     * is identical no matter which layer catches the disabled state.
     */
    axios.post('/me/game', { game_id: id }).catch((error) => {
        if (error.response?.status === 403 && error.response?.data?.message === 'Prize is disabled') {
            selectedGameId.value = previousId;
            const idx = prizes.value.findIndex(p => p.id === id);
            if (idx !== -1) prizes.value[idx].is_enabled = false;
            showGameModal({
                title: t('dashboard.popup.comingSoon.title'),
                message: t('dashboard.popup.comingSoon.message'),
                primaryLabel: '',
                primaryAction: '',
                secondaryLabel: t('dashboard.popup.close'),
            });
            return;
        }
        console.error('Failed to persist prize selection:', error);
    });
}
/** Redesign copy for the selected prize's slot, for the "Target prize" card (null when nothing is selected). */
const selectedPrizeTile = computed(() => {
    const slot = orderedPrizes.value.findIndex(p => p && p.id === selectedGameId.value);
    return slot === -1 ? null : PRIZE_TILES[slot];
});

const currentProgress = computed(() => {
    const g = prizes.value.find(p => p.id == selectedGameId.value)

    return g?.progress ?? { radar_level: 0 }
})

/*
 * Radar map: the regions lit for the selected prize. Each player + prize has
 * its own fixed order (regionOrder), and the first radar_level regions in it
 * are lit, so reloading never reshuffles the map.
 */
const authUserId = computed(() => usePage().props.auth?.user?.id ?? null);

const foundRegions = computed(() => {
    if (selectedGameId.value == null) return [];
    const level = Math.max(0, Math.min(6, Number(currentProgress.value.radar_level) || 0));
    return regionOrder(authUserId.value, selectedGameId.value).slice(0, level);
});

/* The region the last scan lit (it flashes) and the note shown on the map until the next scan. */
const freshRegion = ref(null);
const lastFind = ref(null);

watch(
    () => [selectedGameId.value, foundRegions.value.length],
    ([gameId, count], [previousGameId, previousCount]) => {
        if (gameId !== previousGameId) {
            freshRegion.value = null;
            lastFind.value = null;
        } else if (count > previousCount) {
            freshRegion.value = foundRegions.value[count - 1];
            lastFind.value = { count, region: freshRegion.value };
        }
    },
);

watch(scanning, (isScanning) => {
    if (isScanning) {
        freshRegion.value = null;
        lastFind.value = null;
    }
});

const mapNote = computed(() => {
    if (!lastFind.value) return '';
    if (lastFind.value.count >= 6) return t('dashboard.radar.map.allFound');
    return t('dashboard.radar.map.found', { region: t(`dashboard.radar.region.${lastFind.value.region}`) });
});

function setGameProgress(gameId, progress) {
    const idx = prizes.value.findIndex(g => g.id === gameId);
    if (idx !== -1) {
        prizes.value[idx].progress = progress;
    }
}

function playPopupSound() {
    if (!audioEnabled.value) return;
    const popupSound = document.getElementById('popupSound');
    if (popupSound) {
        popupSound.currentTime = 0;
        popupSound.play().catch(error => {
            console.warn("Autoplay for popup sound prevented:", error);
        });
    }
};

const playClickSound = () => {
    if (!audioEnabled.value) return;
    // A modal is open (or just opened in this tick): the popup sound is
    // already playing, so skip the click to avoid two sounds at once.
    if (gameModalShow.value) return;
    const clickSound = document.getElementById('clickSound');
    if (clickSound) {
        clickSound.currentTime = 0;
        clickSound.play().catch(error => {
            console.warn("Autoplay for click sound prevented:", error);
        });
    }
};

/**
 * Open a top-bar overlay (winners/help/settings). The click sound is
 * dispatched with setTimeout(…, 0) (a task, not a microtask) so the
 * browser paints the overlay open first and decodes the click mp3 after.
 * queueMicrotask would have run before paint, which on first-interaction
 * Safari/iOS added 50–200ms of mp3 decode into the critical render path.
 * The play() call still happens inside the same user-gesture turn, so the
 * browser's autoplay policy does not block it.
 */
function openOverlay(name) {
    activeOverlay.value = name;
    setTimeout(playClickSound, 0);
}

/**
 * Stop the middle radar.webm and rewind it so v-show="scanning" hides a
 * clean first frame instead of whatever frame it happened to pause on.
 * Called at every place the scan ends — normal completion, the three
 * server-side error branches (402 / 403 / 423), and the post-animation
 * cleanup block. Safe to call when the ref is null or already paused.
 */
function pauseRadarVideo() {
    const el = radarVideo.value;
    if (!el) return;
    try {
        el.pause();
        el.currentTime = 0;
    } catch { /* no-op */ }
}

function playScanSoundSequence() {
  if (!audioEnabled.value) return;

  const a1 = document.getElementById('scanSound');
  const a2 = document.getElementById('scanSound2');
  
  if (!a1 || !a2) return;

  // Reset both audio elements to prevent overlap
  [a1, a2].forEach(a => {
    a.loop = false;
    a.pause();
    a.currentTime = 0;
  });

  // When radar.mp3 ends, play radar2.mp3
  a1.addEventListener('ended', () => {
    a2.currentTime = 0;
    a2.play().catch(console.error);
  }, { once: true });

  // Start playing radar.mp3
  a1.play().catch(console.error);
}

async function startScan() {
    if (scanning.value) return;

    const prize = selectedPrize.value;
    const rules = prize ? getPrizeRules(prize.name) : null;
    const balance = Number(walletBalance.value) || 0;

    if (selectedGameId.value == null) {
        showGameModal({
            title: t('dashboard.popup.selectPrize.title'),
            message: t('dashboard.popup.selectPrize.message'),
            primaryLabel: t('dashboard.popup.howToPlay'),
            primaryAction: 'openHelp',
            secondaryLabel: t('dashboard.popup.ok'),
        });
        return;
    }

    /*
     * Wallet vs scan cost: Game::attemptScan debits `price_to_play`, which is
     * the Radar Cash cost per scan. Use the minimum-deposit copy when balance
     * is too low.
     */
    const scanCost = rules ? rules.scanCostRadars : 0;
    if (rules && balance < scanCost) {
        const message = minDepositMessage(prize.name);
        showMinDepositModal(message);
        return;
    }

    playScanSoundSequence();

    scanning.value = true;
    detectionStatus.value = 'searching';
    visibleCount.value = 0;
    antennaIconSrc.value = '/assets/imgs/an.png'; // Reset to default before scan

    /*
     * Drive the radar.webm playback explicitly rather than letting the
     * element's `autoplay` attribute do it on page mount.
     *
     * The previous version combined `autoplay` + `loop` + a `setTimeout`
     * pause. The setTimeout fired on a wall-clock 16 seconds from the
     * click — so:
     *   - on early-exit errors (402 / 403 / 423) it would still pause the
     *     video 16 s later, even though no scan was in flight;
     *   - if the user kicked off another scan in the meantime, the stale
     *     timer would pause the fresh scan's video roughly a second in,
     *     which is the "video disappears after ~1 sec" symptom.
     *
     * We now rewind to 0 and call play() here, and rely on the matching
     * pauseRadarVideo() at every exit of startScan (success, all error
     * branches, and the post-animation cleanup block) to stop it. The
     * `loop` attribute on the element keeps the animation going for as
     * long as the scan takes.
     */
    if (radarVideo.value) {
        try {
            radarVideo.value.currentTime = 0;
            const p = radarVideo.value.play();
            if (p && typeof p.catch === 'function') {
                p.catch(() => { /* autoplay-policy failure is non-fatal */ });
            }
        } catch { /* no-op */ }
    }

    let found = false;
    let dat;
    try {
        const { data } = await axios.post('/scan/' + selectedGameId.value);
        found = !!data.antenna_detected;
        dat = data;
        if (data && data.wallet != null) {
            walletBalance.value = Number(data.wallet);
        }
    } catch (err) {
        const status = err.response?.status;
        const payload = err.response?.data || {};

        /*
         * Server is the source of truth for Radar Cash. Every error response
         * from /scan now includes the authoritative wallet value, so we sync
         * the UI immediately — this is what fixes "money not deducted until I
         * reload". If the error carries no payload (network drop), we fall
         * back to GET /me.
         */
        if (payload.wallet != null) {
            walletBalance.value = Number(payload.wallet);
        } else {
            refreshWalletFromServer();
        }

        if (status === 403 && payload.message === 'Prize is disabled') {
            scanning.value = false;
            detectionStatus.value = 'idle';
            pauseRadarVideo();
            showGameModal({
                title: t('dashboard.popup.comingSoon.title'),
                message: t('dashboard.popup.comingSoon.message'),
                primaryLabel: '',
                primaryAction: '',
            });
            return;
        }

        if (status === 402) {
            scanning.value = false;
            detectionStatus.value = 'idle';
            pauseRadarVideo();
            const message = minDepositMessage(prize?.name);
            showMinDepositModal(message);
            return;
        }

        if (status === 423) {
            scanning.value = false;
            detectionStatus.value = 'idle';
            pauseRadarVideo();
            showGameModal({
                title: t('dashboard.popup.radarOffline.title'),
                message: t('dashboard.popup.radarOffline.message'),
                primaryLabel: '',
                primaryAction: '',
            });
            return;
        }

        found = false;
    }

    const total = originalColors.length;
    const totalMs = 16000;
    const interval = totalMs / total;

    for (let i = 1; i <= total; i++) {
        visibleCount.value = i;
        await sleep(interval);
    }

    detectionStatus.value = found ? 'found' : 'not-found';
    scanning.value = false;
    pauseRadarVideo();
    if (dat) {
        setGameProgress(selectedGameId.value, dat.progress);
    }

    if (found) {
        antennaIconSrc.value = '/assets/imgs/an2.png';
    } else {
        antennaIconSrc.value = '/assets/imgs/an3.png'; // antenna not detected
    }

    await sleep(2000);
    detectionStatus.value = 'idle';
    visibleCount.value = 0;

    /*
     * Win celebration. The 6th successful antenna completes the radar; that's
     * the moment to celebrate. We gate on `radar_level >= 6` AND `found` so a
     * passive page reload that already shows level 6 won't re-fire the popup
     * — only an actual scan that just completed the set does.
     */
    const radarLevel = Number(dat?.progress?.radar_level ?? 0);
    const justWon = found && radarLevel >= 6;
    if (justWon) {
        celebrateWin(prize?.name);
    } else if (found && radarLevel > 0 && radarLevel < 6) {
        showAntennaProgressModal(radarLevel, prize?.name);
    }

    /*
     * Post-scan cleanup (runs ONLY after the full result animation + the 2s
     * "found/not-found" display above). We:
     *
     *   1. Reset the antenna icon back to the neutral an.png regardless of
     *      outcome — previously an3.png stuck around on a failed scan until
     *      the next scan started, which looked like the UI was still showing
     *      stale state from the last attempt.
     *
     *   2. Auto-deselect the prize slot. Between scans we want a clean
     *      "nothing chosen" board so the next click feels like a fresh
     *      decision instead of a pre-committed one. We clear selectedGameId
     *      locally and fire-and-forget a POST /me/game with game_id=null so
     *      the server's users.game_id column stays in sync (otherwise a page
     *      reload would snap the previous prize back into place).
     *
     * The /me/game endpoint accepts null as of the matching UserController
     * change; any failure here is non-fatal and only affects persistence —
     * the local UI still clears.
     */
    antennaIconSrc.value = '/assets/imgs/an.png';

    /*
     * Keep the prize selected between scans (client request). We only clear it
     * when the wallet can no longer afford another scan of that prize, which
     * mirrors the balance < scanCost gate in selectPrize(). walletBalance was
     * refreshed from the scan response above, so this is the post-scan value.
     */
    if (selectedGameId.value != null) {
        const stillSelected = prizes.value.find(p => p.id == selectedGameId.value);
        const rules = stillSelected ? getPrizeRules(stillSelected.name) : null;
        const scanCost = rules?.scanCostRadars ?? 0;

        if ((Number(walletBalance.value) || 0) < scanCost) {
            selectedGameId.value = null;
            axios.post('/me/game', { game_id: null }).catch((error) => {
                console.error('Failed to clear prize selection after scan:', error);
            });
        }
    }
}
async function fetchRadarStatus() {
    try {
        const { data } = await axios.get('/radar/status');
        radarOnline.value = !!data.online;
    } catch {  }
}
const toggleMusic = () => {
    audioEnabled.value = !audioEnabled.value; try { localStorage.setItem("radarleb:audioEnabled", audioEnabled.value ? "1" : "0"); } catch {}
    const allAudioElements = document.querySelectorAll('audio');
    allAudioElements.forEach(audioEl => {
        if (audioEnabled.value) {
        } else {
            audioEl.pause();
            audioEl.currentTime = 0;
        }
    });

    if (!audioEnabled.value && hornInterval) {
        clearInterval(hornInterval);
        hornInterval = null; // Clear the interval ID
    } else if (audioEnabled.value && !hornInterval) {
        const playHornSound = () => {
            const hornSound = document.getElementById('hornSound');
            if (hornSound && audioEnabled.value) {
                hornSound.play().catch(error => {
                    console.warn("Autoplay for horn sound prevented (re-enabled):", error);
                });
            }
        };
        playHornSound();
        hornInterval = setInterval(playHornSound, 180000);
    }
};

const handleLogout = () => {
    router.post('/logout');
};

const resetPasswordFlow = () => {
    oldPassword.value = '';
    newPassword.value = '';
    confirmPassword.value = '';
    oldPasswordVerified.value = false;
    wrongAttempts.value = 0;
    passwordError.value = '';
    newPasswordError.value = '';
    confirmPasswordError.value = '';
    currentPage.value = 'settings';
};

const verifyOldPassword = async () => {
    if (!oldPassword.value) {
        passwordError.value = t('dashboard.password.error.enterOld');
        return;
    }

    verifyingPassword.value = true;
    passwordError.value = '';

    try {
        const response = await axios.post('/settings/password/verify', {
            old_password: oldPassword.value
        });

        if (response.data.verified) {
            oldPasswordVerified.value = true;
            wrongAttempts.value = 0;
            passwordError.value = '';
        } else {
            wrongAttempts.value++;
            if (wrongAttempts.value >= 3) {
                passwordError.value = t('dashboard.password.error.tooManyAttempts');
            } else {
                passwordError.value = t('dashboard.password.error.attemptsLeft', { count: 3 - wrongAttempts.value });
            }
        }
    } catch (error) {
        if (error.response?.data?.message) {
            passwordError.value = error.response.data.message;
        } else {
            passwordError.value = t('dashboard.password.error.generic');
        }
        wrongAttempts.value++;
        if (wrongAttempts.value >= 3) {
            passwordError.value = t('dashboard.password.error.tooManyAttempts');
        }
    } finally {
        verifyingPassword.value = false;
    }
};

const updatePassword = async () => {
    // Clear previous errors
    newPasswordError.value = '';
    confirmPasswordError.value = '';

    // Validation
    if (!newPassword.value) {
        newPasswordError.value = t('dashboard.password.error.enterNew');
        return;
    }

    if (newPassword.value.length < 8) {
        newPasswordError.value = t('dashboard.password.error.minLength');
        return;
    }

    if (!confirmPassword.value) {
        confirmPasswordError.value = t('dashboard.password.error.confirmNew');
        return;
    }

    if (newPassword.value !== confirmPassword.value) {
        confirmPasswordError.value = t('dashboard.password.error.mismatch');
        return;
    }

    updatingPassword.value = true;

    try {
        const response = await axios.post('/settings/password', {
            old_password: oldPassword.value,
            new_password: newPassword.value,
            new_password_confirmation: confirmPassword.value
        });

        // Show success message
        showSuccessMessage.value = true;
        
        // Reset form and return to settings after 2 seconds
        setTimeout(() => {
            resetPasswordFlow();
            showSuccessMessage.value = false;
        }, 2000);
    } catch (error) {
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors;
            if (errors.new_password) {
                newPasswordError.value = errors.new_password[0];
            }
            if (errors.new_password_confirmation) {
                confirmPasswordError.value = errors.new_password_confirmation[0];
            }
            if (errors.old_password) {
                passwordError.value = errors.old_password[0];
            }
        } else if (error.response?.data?.message) {
            passwordError.value = error.response.data.message;
        } else {
            passwordError.value = t('dashboard.password.error.generic');
        }
    } finally {
        updatingPassword.value = false;
    }
};

function showLocationError(title, message) {
    showGameModal({ title, message });
}

function startLocationWatch() {
    if (locationWatchId.value != null || !navigator.geolocation) return;
    locationWatchId.value = navigator.geolocation.watchPosition(
        (position) => {
            userLocation.value = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            locationUrl.value = `https://www.google.com/maps?q=${userLocation.value.lat},${userLocation.value.lng}`;
        },
        (err) => {
            console.warn('Location watch error:', err);
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
    );
}

/**
 * Warm up coordinates on load if the user has already granted permission.
 * Silent: never triggers a prompt. When it succeeds, onLocationTap can open
 * a new tab synchronously from the tap, which is the fast path.
 */
async function prefetchLocationIfPermitted() {
    if (!navigator.geolocation || !navigator.permissions?.query) return;
    try {
        const status = await navigator.permissions.query({ name: 'geolocation' });
        if (status.state === 'granted') startLocationWatch();
    } catch {
        // Permissions API unsupported; fall back to on-tap request.
    }
}
prefetchLocationIfPermitted();

/**
 * My Location.
 *
 * Never open a tab before coordinates exist: doing so backgrounds this tab,
 * and browsers suspend geolocation in background tabs, which makes the
 * request fail with POSITION_UNAVAILABLE/TIMEOUT. So:
 *   - coordinates already known -> open a new tab straight from the tap
 *   - otherwise -> resolve position first, then navigate in THIS tab
 *     (same-tab navigation is never popup-blocked), and surface any error
 *     as a modal before going anywhere.
 */
function onLocationTap() {
    const now = Date.now();
    if (now - lastLocationTapAt < LOCATION_TAP_DEBOUNCE_MS) return;
    lastLocationTapAt = now;

    if (locationRequestInProgress.value) {
        playClickSound();
        return;
    }

    // Fast path: we already have a fix, so keep the game open in this tab.
    if (userLocation.value.lat != null && userLocation.value.lng != null) {
        playClickSound();
        window.open(locationUrl.value, '_blank', 'noopener');
        return;
    }

    if (!navigator.geolocation) {
        showLocationError(t('dashboard.popup.location.unavailable'), t('dashboard.popup.location.unsupported'));
        playClickSound();
        return;
    }

    if (typeof window !== 'undefined' && !window.isSecureContext) {
        showLocationError(t('dashboard.popup.location.unavailable'), t('dashboard.popup.location.https'));
        playClickSound();
        return;
    }

    locationRequestInProgress.value = true;
    playClickSound();

    const onSuccess = (position) => {
        locationRequestInProgress.value = false;
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        userLocation.value = { lat, lng };
        locationUrl.value = `https://www.google.com/maps?q=${lat},${lng}`;
        startLocationWatch();
        // The tap gesture is spent by the time the async callback runs, so
        // window.open() here would be popup-blocked. An anchor click is a
        // fresh gesture, so route it through the modal's primary <a href>
        // with target="_blank": the game tab is never navigated away from.
        // Only the first grant sees this; afterwards prefetchLocationIfPermitted
        // warms the coords and onLocationTap's fast path opens a tab directly.
        showGameModal({
            title: t('dashboard.popup.location.found'),
            message: t('dashboard.popup.location.openMaps'),
            primaryLabel: t('dashboard.popup.location.openMap'),
            primaryRoute: locationUrl.value,
            primaryNewTab: true,
        });
    };

    const onFinalError = (error) => {
        locationRequestInProgress.value = false;
        const msg =
            error.code === 1
                ? t('dashboard.popup.location.denied')
                : t('dashboard.popup.location.failed');
        showLocationError(t('dashboard.popup.location.unavailable'), msg);
    };

    // High accuracy can time out indoors or on desktop; retry once coarsely
    // before telling the user it failed.
    navigator.geolocation.getCurrentPosition(
        onSuccess,
        (firstError) => {
            if (firstError.code === 1) {
                onFinalError(firstError);
                return;
            }
            navigator.geolocation.getCurrentPosition(
                onSuccess,
                onFinalError,
                { enableHighAccuracy: false, timeout: 20000, maximumAge: 60000 }
            );
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}


/** Prize selection visual state is now driven by template (:src binding on prize items). Kept for any other side effects. */
const updatePrizeSelectionUI = () => {};

let hornInterval = null;


async function refreshGamesFromApi() {
    try {
        const { data } = await axios.get('/games');
        if (Array.isArray(data) && data.length > 0) {
            prizes.value = data;
        }
    } catch { /* silent — Inertia props are the primary source */ }
}

/**
 * Force-reads the authoritative wallet balance from the server.
 * Called whenever the scan endpoint fails without a payload (e.g. network
 * blip) so Radar Cash never drifts from the DB value.
 */
async function refreshWalletFromServer() {
    try {
        const { data } = await axios.get('/me');
        if (data && data.wallet_balance != null) {
            walletBalance.value = Number(data.wallet_balance);
        }
    } catch { /* silent */ }
}

onMounted(() => {
    /*
     * Mobile-only gate. When the page renders on desktop we cover the UI
     * with <DesktopBlock /> and skip the entire setup: no game polling,
     * no audio preload, no background video resume guard, no A2HS, no
     * Inertia route guards. None of those make sense without a phone in
     * hand, and skipping them avoids spurious console noise (autoplay
     * blocked, geolocation prompts, etc.) on a machine that won't play.
     */
    if (isDesktop.value) {
        return;
    }

    if (!prizes.value || prizes.value.length === 0) {
        refreshGamesFromApi();
    }

    /*
     * Poll at a sane interval (30 s) and only while the tab is visible.
     * Previously a 5 s interval piled up on `php artisan serve` whenever a
     * single response was slow, which starved icons/videos from loading at all.
     */
    fetchRadarStatus();
    refreshWalletFromServer();
    let radarPollId = null;
    let walletPollId = null;
    const startRadarPolling = () => {
        if (radarPollId != null) return;
        radarPollId = setInterval(fetchRadarStatus, 30_000);
    };
    const startWalletPolling = () => {
        if (walletPollId != null) return;
        walletPollId = setInterval(refreshWalletFromServer, 10_000);
    };
    const stopRadarPolling = () => {
        if (radarPollId == null) return;
        clearInterval(radarPollId);
        radarPollId = null;
    };
    const stopWalletPolling = () => {
        if (walletPollId == null) return;
        clearInterval(walletPollId);
        walletPollId = null;
    };
    startRadarPolling();
    startWalletPolling();
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopRadarPolling();
            stopWalletPolling();
        } else {
            fetchRadarStatus();
            refreshWalletFromServer();
            startRadarPolling();
            startWalletPolling();
        }
    });

    // Scan audio sequence is handled by playScanSoundSequence() function
    
    // Add error listeners to DOM audio element for debugging
    const a2 = document.getElementById('scanSound2');
    if (a2) {
        a2.addEventListener('error', () => {
            console.error('scanSound2 DOM element error:', a2.error, a2.src, {
                readyState: a2.readyState,
                networkState: a2.networkState,
                duration: a2.duration
            });
        }, { once: false });
        
        a2.addEventListener('loadedmetadata', () => {
            console.log('scanSound2 loadedmetadata:', {
                src: a2.src,
                readyState: a2.readyState,
                duration: a2.duration,
                networkState: a2.networkState
            });
        }, { once: false });
        
        a2.addEventListener('canplaythrough', () => {
            console.log('scanSound2 canplaythrough:', {
                src: a2.src,
                readyState: a2.readyState,
                duration: a2.duration
            });
        }, { once: false });
        
        // Log initial state
        console.log('scanSound2 initial state:', {
            src: a2.src,
            readyState: a2.readyState,
            networkState: a2.networkState,
            duration: a2.duration
        });
    }

    /*
     * Keep the page background video eager so it paints before the fallback
     * background after auth redirects. The heavier in-radar scan video still
     * waits until idle so it does not compete with first render.
     */
    nextTick(() => {
        const bgVid = document.getElementById('myVideo');
        if (bgVid) {
            bgVid.muted = true;
            bgVid.defaultMuted = true;
            bgVid.playsInline = true;
            bgVid.load();
            bgVid.play().catch((e) => console.warn('Autoplay failed:', e));
        }

        /*
         * Mobile resume guard. iOS Safari pauses background videos in a
         * handful of situations the page has no warning of:
         *   - the user locks the phone or switches apps and comes back,
         *   - Low Power Mode silently freezes the playback,
         *   - an incoming call / notification briefly takes audio focus,
         *   - the OS reclaims memory and tears the decoder down.
         *
         * After any of those, the <video> stays paused on its last
         * frame and looks like a static image. We attach lightweight
         * listeners so any unexpected pause / visibility-return kicks
         * playback back on. We do NOT touch myVideo2 — that one is
         * intentionally play/paused by startScan() and pauseRadarVideo().
         */
        if (bgVid) {
            const tryResume = () => {
                bgVid.muted = true;
                bgVid.defaultMuted = true;
                bgVid.playsInline = true;
                if (bgVid.readyState === 0) {
                    bgVid.load();
                }
                if (bgVid.paused && !bgVid.ended) {
                    const p = bgVid.play();
                    if (p && typeof p.catch === 'function') {
                        p.catch(() => { /* autoplay-policy retry — non-fatal */ });
                    }
                }
            };
            bgVid.addEventListener('pause', tryResume);
            // Some browsers fire `stalled` / `suspend` instead of `pause`
            // when memory pressure tears the decoder down.
            bgVid.addEventListener('loadeddata', tryResume);
            bgVid.addEventListener('canplay', tryResume);
            bgVid.addEventListener('stalled', tryResume);
            bgVid.addEventListener('suspend', tryResume);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) tryResume();
            });
            window.addEventListener('pageshow', tryResume);
            window.addEventListener('focus', tryResume);
            bgVideoResumeId = setInterval(() => {
                if (!document.hidden) tryResume();
            }, 1500);
        }
    });

    const attachVideoSources = () => {
        videoSrcsReady.value = true;
        nextTick(() => {
            radarVideo.value?.load();
        });
    };
    const scheduleVideoLoad = () => {
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(attachVideoSources, { timeout: 1500 });
        } else {
            setTimeout(attachVideoSources, 300);
        }
    };
    if (document.readyState === 'complete') {
        scheduleVideoLoad();
    } else {
        window.addEventListener('load', scheduleVideoLoad, { once: true });
    }
    setTimeout(() => {
        loading.value = false;
        if (radarVideo.value) {
            radarVideo.value.pause();
            radarVideo.value.currentTime = 0;
        }

        const playHornSound = () => {
            const hornSound = document.getElementById('hornSound');
            if (hornSound && audioEnabled.value) { // Check audioEnabled here
                hornSound.play().catch(error => {
                    console.warn("Autoplay for horn sound prevented:", error);
                });
            }
        };

        if (audioEnabled.value) {
            playHornSound();
            hornInterval = setInterval(playHornSound, 180000);
        }

        if (props.showHelpOnLoad) {
            activeOverlay.value = 'help';
        }

    }, 2000);

    // Preload detected images for prize items (optional, for instant switch when selected)
    PRIZE_DISPLAY_CONFIG.forEach((cfg) => {
        if (cfg.detectedImg) {
            const preloadImg = new Image();
            preloadImg.src = cfg.detectedImg;
        }
    });

    /*
     * Click-sound DOM listeners were previously attached here for radar-cash,
     * menu items, help back button and settings buttons. They ran in parallel
     * with Vue @click handlers, firing twice on each tap, which on low-end
     * mobile feels like "the button takes a while to register" (the second
     * play() is queued behind the first). Vue @click is the sole driver now.
     */

    updatePrizeSelectionUI();
});

onUnmounted(() => {
    if (hornInterval) {
        clearInterval(hornInterval);
    }
    if (bgVideoResumeId) {
        clearInterval(bgVideoResumeId);
        bgVideoResumeId = null;
    }
    if (locationWatchId.value != null && navigator.geolocation) {
        navigator.geolocation.clearWatch(locationWatchId.value);
        locationWatchId.value = null;
    }
});

watch(selectedGameId, updatePrizeSelectionUI);
</script>

<style>

/*
 * Location tile — the rightmost slot in the bottom action row.
 *
 * The visible "MY LOCATION" text label was retired per the Figma "Main
 * Page" — only the cyan rounded-square icon remains. The container is the
 * grid item that hosts the <button>, and the button itself fills the tile;
 * the canonical sizing (height matched to --rl-scan-h, square aspect) lives
 * with the rest of the .button-row rules in resources/css/app.css.
 */
.location-container {
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: auto;
}

.location-button {
    -webkit-tap-highlight-color: transparent;
    pointer-events: auto;
    touch-action: manipulation;
}
.location-button-img {
    pointer-events: none;
}
.location-button:focus {
    outline: none;
}
.location-button:focus-visible {
    outline: 2px solid rgba(255, 255, 255, 0.6);
    outline-offset: 2px;
}

/*
 * Locale switcher slot inside .bar-right.
 *
 * The previous `.locale-switcher-floating` (position: fixed; top: 12px;
 * inset-inline-end: 12px) put EN/AR exactly where the new top-bar LOGOUT
 * button now lives, so the two stacked. The switcher now sits inside
 * `.bar-right` as the first item (visually leftmost in the right cluster),
 * separated from the icon group by the .bar-right gap. A small right margin
 * + a vertical hairline create a visual boundary so it doesn't read as part
 * of the icon row.
 */
/*
 * RADAR CASH balance pill in the top bar. Uses the same cyan-tinted glass
 * treatment as the radar status indicator so the eye groups them together
 * as "live state about my account". Compact pill on mobile (icon hidden
 * <380px to save horizontal room next to the icon cluster).
 */
.bar-balance {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 7px 15px;
    border-radius: 999px;
    background: rgba(98, 195, 255, 0.12);
    border: 1px solid rgba(98, 195, 255, 0.35);
    color: var(--rl-color-text);
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.04em;
    line-height: 1;
    margin-inline-start: 8px;
    margin-inline-end: 12px;
    white-space: nowrap;
}
.bar-balance__icon {
    width: 19px;
    height: 19px;
    object-fit: contain;
    flex-shrink: 0;
    pointer-events: none;
}
.bar-balance__value {
    line-height: 1;
}
@media (max-width: 576px) {
    .bar-balance {
        padding: 6px 10px;
        font-size: 13px;
        gap: 6px;
        margin-inline-start: 6px;
        margin-inline-end: 8px;
    }
    .bar-balance__icon { width: 17px; height: 17px; }
}
@media (max-width: 380px) {
    /* On the narrowest phones we hide menu-item labels too — drop the icon
       here as well so just the number remains, which is what really matters. */
    .bar-balance { padding: 2px 7px; gap: 0; }
    .bar-balance__icon { display: none; }
}

.bar-locale {
    display: inline-flex;
    align-items: center;
    color: rgba(255, 255, 255, 0.85);
    padding-inline-end: 10px;
    margin-inline-end: 2px;
    border-inline-end: 1px solid rgba(255, 255, 255, 0.12);
}
@media (max-width: 576px) {
    /* Tighten on phones — the icon row is already at gap: 4px here. */
    .bar-locale {
        padding-inline-end: 3px;
        margin-inline-end: 0;
    }
}
@media (max-width: 380px) {
    /* On the narrowest phones the EN/AR pills compete with 4 icons + the
       status dot + the logo. Hide the divider so it doesn't add visual noise
       when every pixel is precious. */
    .bar-locale {
        border-inline-end: 0;
        padding-inline-end: 4px;
    }
}

/* -----------------------------------------------------------------------
 * Radar visualization — Figma "Main Page" parity.
 *
 * The .radar element is a flex container holding the idle ellipse PNG and
 * the in-scan webm video. It used to render as a plain dark circle; Figma
 * shows concentric rings, a crosshair, and a soft cyan glow around the
 * outer rim. We add those purely with CSS so the existing assets keep
 * working unchanged.
 *
 * The rings/crosshair/glow live as pseudo-elements so they don't disturb
 * the flex layout or block clicks on the actual scan video.
 * --------------------------------------------------------------------- */
.radar {
    /*
     * Force a 1:1 aspect ratio so the rings + glow render as a perfect
     * circle regardless of the parent column's dimensions. The radar-col
     * is a Bootstrap col-6 which is wider than tall on desktop — without
     * this the rings stretch into a flat ellipse.
     *
     * `width: min(100%, …)` prevents the radar from overflowing taller
     * viewports and keeps it visually centred inside its column.
     */
    aspect-ratio: 1 / 1;
    width: min(100%, 70vh);
    height: auto !important;
    margin-inline: auto;

    /* Outer rim with cyan glow + a subtle inner gradient. The radial
     * gradient keeps the centre dark so the ellipse PNG sits on top. */
    border-radius: 50%;
    background:
        radial-gradient(circle at center,
            rgba(6, 33, 46, 0.0) 0%,
            rgba(6, 33, 46, 0.0) 55%,
            rgba(98, 195, 255, 0.08) 70%,
            rgba(98, 195, 255, 0.18) 92%,
            rgba(98, 195, 255, 0.0) 100%);
    box-shadow:
        inset 0 0 0 1px rgba(98, 195, 255, 0.18),
        inset 0 0 60px rgba(98, 195, 255, 0.07),
        0 0 20px rgba(98, 195, 255, 0.18);
}

.radar.radar--scanning {
    background: transparent;
    box-shadow: none;
}

.radar.radar--scanning::before,
.radar.radar--scanning::after {
    display: none;
}

/* Concentric rings — drawn with repeating-radial-gradient on a ::before
 * absolutely positioned over the radar. */
.radar::before {
    content: "";
    position: absolute;
    inset: 8%;
    border-radius: 50%;
    background:
        repeating-radial-gradient(circle at center,
            rgba(98, 195, 255, 0.0) 0,
            rgba(98, 195, 255, 0.0) 24%,
            rgba(98, 195, 255, 0.16) 24%,
            rgba(98, 195, 255, 0.16) 24.4%);
    pointer-events: none;
    z-index: 0;
}

/* Crosshair — vertical + horizontal hairline through the centre. Drawn on
 * a ::after, positioned absolutely. The two lines use linear-gradients so
 * they fade out at the edges of the radar (more elegant than hard cuts). */
.radar::after {
    content: "";
    position: absolute;
    inset: 8%;
    border-radius: 50%;
    background:
        linear-gradient(to right,
            transparent 0%,
            rgba(98, 195, 255, 0.0) 5%,
            rgba(98, 195, 255, 0.18) 50%,
            rgba(98, 195, 255, 0.0) 95%,
            transparent 100%) 0 50% / 100% 1px no-repeat,
        linear-gradient(to bottom,
            transparent 0%,
            rgba(98, 195, 255, 0.0) 5%,
            rgba(98, 195, 255, 0.18) 50%,
            rgba(98, 195, 255, 0.0) 95%,
            transparent 100%) 50% 0 / 1px 100% no-repeat;
    pointer-events: none;
    z-index: 0;
}

/* The actual centre image (idle ellipse / scan video) sits ABOVE the rings
 * + crosshair — they're decoration, not interaction. */
.radar .radar-center-asset {
    position: relative;
    z-index: 1;
}

/* -----------------------------------------------------------------------
 * Antenna detection thermometer (left column).
 *
 * The base .bar-container was a flat translucent rectangle. Figma shows a
 * thermometer with horizontal tick marks for each of the 6 antenna levels
 * and a subtle gradient outline so it reads as "ready to fill" even when
 * empty. The .fill-bar inside (gradient red→blue) animates up as antennas
 * are detected — that markup already existed.
 * --------------------------------------------------------------------- */
.bar-container {
    position: relative;
    /* 6 evenly-spaced tick marks (one per antenna). repeating-linear-gradient
     * draws hairlines every 1/6th of the height; the first is hidden by
     * being at the bottom edge. */
    background-image:
        repeating-linear-gradient(
            to top,
            transparent 0,
            transparent calc(100% / 6 - 1px),
            rgba(255, 255, 255, 0.18) calc(100% / 6 - 1px),
            rgba(255, 255, 255, 0.18) calc(100% / 6)
        );
    border: 1px solid rgba(98, 195, 255, 0.18);
    box-shadow:
        0 5px 15px rgba(0, 0, 0, 0.3),
        inset 0 0 12px rgba(98, 195, 255, 0.08);
}

/* -----------------------------------------------------------------------
 * Prize-card selected state (right column).
 *
 * Existing UX: clicking a prize swaps the icon to its "detected" variant.
 * Subtle. Figma shows the picked prize wrapped in a blue/cyan card.
 * Adding a halo ring + cyan tint behind the icon makes the affordance
 * obvious without changing the icon swap behaviour.
 * --------------------------------------------------------------------- */
.prize-item {
    border-radius: 0;
    padding: 0;
    transition: transform 100ms ease;
}

.prize-item:hover,
.prize-item--selected {
    background: transparent;
    box-shadow: none;
}

/* -----------------------------------------------------------------------
 * Scan button — wider pill matching Figma's "SCAN" CTA.
 *
 * The default .btn-custom from app.css renders as a small fixed-width
 * button. Figma shows the SCAN button as the visual anchor of the
 * button row — the widest control between Radar Cash and My Location.
 * --------------------------------------------------------------------- */
#scan.btn-custom {
    min-width: clamp(120px, 22vw, 200px);
    padding-inline: 1.5rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    box-shadow:
        0 4px 14px rgba(98, 195, 255, 0.28),
        inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

#scan.btn-custom:not(:disabled):hover {
    filter: brightness(1.06);
    box-shadow:
        0 6px 18px rgba(98, 195, 255, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.16);
}

</style>

<style scoped>
/*
 * 2026 redesign "Radar" screen (map version). Sizes are from the 390x844
 * design frame. The map takes whatever height is left (up to the design's
 * 404px), so short screens fit without scrolling where they can
 * (.radar-screen scrolls when they can't).
 */
#game {
    background: var(--rl-color-night);
}

.radar-screen {
    position: relative;
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    width: 100%;
    max-width: 480px;
    height: 100%;
    margin: 0 auto;
    padding-top: env(safe-area-inset-top, 0px);
    padding-bottom: env(safe-area-inset-bottom, 0px);
    overflow-x: hidden;
    overflow-y: auto;
    color: var(--rl-color-bone);
    font-family: var(--rl-font-sans);
}

/* ---- Header ---------------------------------------------------------- */
.radar-screen__bar {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: space-between;
    box-sizing: border-box;
    height: 60px;
    padding: 0 12px 0 16px;
    border-bottom: 1px solid var(--rl-color-night-edge);
}

.radar-screen__brand {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.radar-screen__brand-icon {
    display: block;
    width: 30px;
    height: 30px;
}

.radar-screen__brand-name {
    font-family: var(--rl-font-headline);
    font-weight: 800;
    font-size: 20px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    white-space: nowrap;
}

.radar-screen__nav {
    display: flex;
    align-items: center;
    gap: 2px;
}

.radar-screen__locale {
    margin-right: 6px;
}

.radar-screen__icon-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    padding: 0;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: var(--rl-color-bone);
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

/* ---- Shared label ---------------------------------------------------- */
.radar-label {
    font-family: var(--rl-font-mono);
    font-weight: 500;
    font-size: 11px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--rl-color-bone-subtle);
}

/* ---- Radar map ------------------------------------------------------- */
/* Takes the height left over, between 250px and the design's 404px. */
.radar-map-card {
    flex: 1 1 0;
    min-height: 250px;
    max-height: 404px;
    margin: 12px 16px 0;
}

/* ---- Status cards (laid over the top of the map) ----------------------- */
.radar-status {
    position: absolute;
    top: 10px;
    left: 10px;
    right: 10px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
}

.radar-status__side {
    display: flex;
    flex-shrink: 0;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}

.radar-card {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    max-width: 170px;
    padding: 7px 10px;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: 12px;
    background: var(--rl-color-map-overlay);
}

.radar-card .radar-label {
    font-size: 10px;
}

.radar-card--radar {
    align-items: flex-end;
    gap: 5px;
}

.radar-card__value {
    overflow: hidden;
    font-family: var(--rl-font-headline);
    font-weight: 800;
    font-size: 19px;
    line-height: 1.05;
    text-transform: uppercase;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.radar-card__meta {
    font-family: var(--rl-font-mono);
    font-size: 12px;
}

.radar-card__status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: var(--rl-font-mono);
    font-size: 12px;
    text-transform: uppercase;
}

.radar-card__dot {
    flex-shrink: 0;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--rl-color-found);
}

.radar-card__dot--live {
    animation: radar-live 2s ease-out infinite;
}

.radar-card__dot--offline {
    background: var(--rl-color-alert);
}

.radar-card__bars {
    display: flex;
    align-items: flex-end;
    gap: 3px;
    height: 14px;
}

.radar-card__bar {
    width: 5px;
    border-radius: 1px;
    background: var(--rl-color-night-line);
}

/* Lit bars pulse, staggered by the inline animation-delay. */
.radar-card__bar--on {
    background: var(--rl-color-found);
    animation: radar-signal 1.1s ease-in-out infinite alternate;
}

/* North arrow, under the Radar card. */
.radar-north {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    margin-inline-end: 4px;
    font-family: var(--rl-font-mono);
    font-size: 9px;
    color: var(--rl-color-dish-label);
}

/* "Antenna found · North": bottom right of the map until the next scan. */
.radar-note {
    position: absolute;
    right: 10px;
    bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    max-width: calc(100% - 20px);
    box-sizing: border-box;
    padding: 7px 10px;
    border: 1px solid var(--rl-color-found);
    border-radius: 10px;
    background: rgba(11, 17, 16, 0.92);
    color: var(--rl-color-found-bright);
    font-family: var(--rl-font-mono);
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    animation: radar-note-in 300ms ease-out;
}

.radar-note__dot {
    flex-shrink: 0;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--rl-color-found-bright);
}

/* ---- Antenna detection ----------------------------------------------- */
.antenna-panel {
    flex-shrink: 0;
    margin: 12px 16px 0;
    padding: 10px 12px 12px;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: 14px;
    background: var(--rl-color-night);
}

.antenna-panel__head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 8px;
}

.antenna-panel__count {
    font-family: var(--rl-font-mono);
    font-size: 13px;
}

.antenna-panel__slots {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 6px;
}

.antenna-slot {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    box-sizing: border-box;
    height: 52px;
    overflow: hidden;
    border: 1px dashed var(--rl-color-slot-line);
    border-radius: 10px;
    color: var(--rl-color-slot-icon);
}

.antenna-slot__name {
    max-width: 100%;
    font-family: var(--rl-font-mono);
    font-size: 8px;
    line-height: 1.2;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
}

.antenna-slot--found {
    border: 1px solid var(--rl-color-found);
    background: var(--rl-color-found-wash);
    color: var(--rl-color-found-bright);
}

/* ---- Prize picker ---------------------------------------------------- */
.prize-picker {
    flex-shrink: 0;
    margin: 14px 16px 0;
}

.prize-picker__title {
    display: block;
    margin-bottom: 8px;
}

.prize-picker__grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 7px;
}

.prize-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    box-sizing: border-box;
    height: 96px;
    padding: 6px 2px;
    border: 1px solid var(--rl-color-dialog-edge);
    border-radius: 12px;
    background: var(--rl-color-night-raised);
    color: var(--rl-color-bone-muted);
    text-align: center;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

/* Nothing picked yet: the tiles pulse in a gentle wave so people know to pick one. */
.prize-picker--prompt .prize-tile {
    animation: prize-nudge 2.4s ease-in-out infinite;
    animation-delay: calc(var(--tile-index) * 0.15s);
}

.prize-picker--prompt .prize-picker__title {
    animation: prize-title 2.4s ease-in-out infinite;
}

.prize-tile--selected {
    border: 1.5px solid var(--rl-color-signal);
    background: var(--rl-color-signal-wash);
    color: var(--rl-color-bone);
}

.prize-tile__name {
    font-size: 11px;
    font-weight: 600;
    line-height: 1.1;
}

.prize-tile__price {
    font-family: var(--rl-font-mono);
    font-size: 11px;
}

/* ---- Bottom actions -------------------------------------------------- */
/* margin-top:auto pins the row to the bottom; padding keeps a gap on short screens. */
.radar-actions {
    display: grid;
    flex-shrink: 0;
    grid-template-columns: 72px minmax(0, 1fr) 72px;
    gap: 12px;
    align-items: start;
    margin: auto 16px 22px;
    padding-top: 14px;
}

.radar-actions__side {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.radar-actions__tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    box-sizing: border-box;
    width: 72px;
    height: 64px;
    padding: 0;
    border: 1px solid var(--rl-color-night-line);
    border-radius: 14px;
    background: var(--rl-color-night-raised);
    color: var(--rl-color-bone);
}

button.radar-actions__tile {
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.radar-actions__balance {
    font-family: var(--rl-font-mono);
    font-size: 14px;
}

.radar-actions__label {
    font-family: var(--rl-font-mono);
    font-size: 10px;
    line-height: 1.3;
    letter-spacing: 0.1em;
    text-align: center;
    text-transform: uppercase;
    color: var(--rl-color-bone-subtle);
}

/* Background colour comes from buttonStyle (idle / scanning / not-found). */
.radar-scan {
    height: 64px;
    padding: 0 8px;
    border: 0;
    border-radius: var(--rl-radius-button);
    color: #ffffff;
    font-family: var(--rl-font-headline);
    font-weight: 800;
    font-size: 28px;
    line-height: 1;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    transition: background-color 0.3s ease, transform 220ms ease-out, filter 220ms ease-out;
}

.radar-scan:active:not(:disabled) {
    transform: scale(0.96);
    filter: brightness(1.15);
    transition-duration: 0.3s, 60ms, 60ms;
}

.radar-scan:disabled {
    opacity: 0.5;
    cursor: default;
}

/* Disabled while scanning is expected; keep it at full strength like the design. */
.radar-scan--busy:disabled {
    opacity: 1;
}

/* "Antenna not detected", "Coming soon", ... */
.radar-scan--long {
    font-size: 18px;
    letter-spacing: 0.06em;
}

/*
 * Missed scan: dark button with a bright red edge and glow, a quick shake,
 * and "Try again" under the message, so a miss can't be mistaken for the
 * red Scan or the dark-red Scanning state.
 */
.radar-scan--missed {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    box-sizing: border-box;
    border: 2px solid var(--rl-color-alert);
    background: var(--rl-color-miss);
    box-shadow: 0 0 0 4px var(--rl-color-signal-wash), 0 0 24px var(--rl-color-signal-glow);
    animation: radar-miss-shake 420ms ease-out, radar-miss-glow 1s ease-in-out 420ms infinite alternate;
}

.radar-scan__miss {
    color: var(--rl-color-alert);
    font-size: 19px;
    letter-spacing: 0.04em;
    white-space: nowrap;
}

.radar-scan__retry {
    font-family: var(--rl-font-sans);
    font-weight: 500;
    font-size: 13px;
    letter-spacing: 0.02em;
    text-transform: none;
    color: var(--rl-color-bone);
}

/*
 * Press feedback for every tappable control. The press snaps in (60 ms) and
 * releases slowly (220 ms), so even a quick tap shows.
 */
.radar-screen__icon-btn,
.prize-tile,
button.radar-actions__tile {
    transition: transform 220ms ease-out, background-color 220ms ease-out, border-color 220ms ease-out;
}

.radar-screen__icon-btn:active {
    background: var(--rl-color-press-wash);
    transform: scale(0.9);
    transition-duration: 60ms;
}

.prize-tile:active,
button.radar-actions__tile:active {
    background: var(--rl-color-press-wash);
    border-color: var(--rl-color-bone-muted);
    transform: scale(0.94);
    transition-duration: 60ms;
}

.radar-screen__icon-btn:focus-visible,
.radar-actions__tile:focus-visible,
.radar-scan:focus-visible,
.prize-tile:focus-visible {
    outline: 2px solid var(--rl-color-bone);
    outline-offset: 2px;
}

/*
 * ---- Arabic --------------------------------------------------------------
 * The design faces have no Arabic glyphs; Arabic falls through to IBM Plex
 * Sans Arabic (see --rl-font-*). Letter-spacing pulls Arabic letters apart,
 * and 10-11px Arabic is hard to read, so tracking goes and sizes step up.
 */
.radar-screen:lang(ar) .radar-label,
.radar-screen:lang(ar) .radar-card__status,
.radar-screen:lang(ar) .radar-card__value,
.radar-screen:lang(ar) .radar-actions__label,
.radar-screen:lang(ar) .radar-scan,
.radar-screen:lang(ar) .radar-scan__miss,
.radar-screen:lang(ar) .radar-note,
.radar-screen:lang(ar) .antenna-slot__name {
    letter-spacing: 0;
}

/* Mono's fixed-width space opens big gaps between Arabic words: use the sans stack. */
.radar-screen:lang(ar) .radar-label,
.radar-screen:lang(ar) .radar-card__status,
.radar-screen:lang(ar) .radar-card__meta,
.radar-screen:lang(ar) .radar-actions__label,
.radar-screen:lang(ar) .radar-north,
.radar-screen:lang(ar) .radar-note,
.radar-screen:lang(ar) .antenna-slot__name {
    font-family: var(--rl-font-sans);
}

.radar-screen:lang(ar) .radar-label { font-size: 12px; }
.radar-screen:lang(ar) .radar-card .radar-label { font-size: 11px; }
.radar-screen:lang(ar) .radar-card__status { font-size: 13px; }
.radar-screen:lang(ar) .radar-card__value { line-height: 1.25; }
/* Every tile reserves two lines for its name, so all five prices sit on one line. */
.radar-screen:lang(ar) .prize-tile__name {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 2.5em;
    font-size: 11.5px;
    line-height: 1.25;
}
.radar-screen:lang(ar) .radar-actions__label { font-size: 11px; }
.radar-screen:lang(ar) .radar-north { font-size: 11px; }
.radar-screen:lang(ar) .radar-note { font-size: 12px; }
.radar-screen:lang(ar) .antenna-slot__name { font-size: 9.5px; }
.radar-screen:lang(ar) .radar-scan__miss { font-size: 20px; }
/* Arabic letters are taller than the Latin caps: give the two lines room. */
.radar-screen:lang(ar) .radar-scan--missed { gap: 3px; line-height: 1.3; }

/* ---- Short screens (most phones with browser chrome) ------------------ */
@media (max-height: 740px) {
    .radar-screen__bar { height: 52px; }
    .radar-map-card { margin-top: 8px; }
    .radar-card { padding: 6px 9px; }
    .antenna-panel { margin-top: 10px; padding: 8px 10px 10px; }
    .antenna-slot { height: 44px; gap: 2px; }
    .prize-picker { margin-top: 10px; }
    .prize-tile { height: 80px; }
    .radar-screen:lang(ar) .prize-tile { gap: 3px; padding: 4px 2px; }
    .radar-actions { margin-bottom: 14px; padding-top: 10px; }
    .radar-actions__tile,
    .radar-scan { height: 56px; }
}

/* ---- Narrow phones ---------------------------------------------------- */
@media (max-width: 370px) {
    .radar-screen__bar { padding: 0 8px 0 12px; }
    .radar-screen__icon-btn { width: 38px; }
}

@media (max-width: 340px) {
    .radar-screen__brand-name { display: none; }
    .radar-scan { font-size: 20px; }
    .radar-scan--long { font-size: 14px; }
    .radar-scan__miss { font-size: 15px; }
    .radar-scan__retry { font-size: 12px; }
    .antenna-slot__name { font-size: 7px; letter-spacing: 0; }
}

@keyframes radar-note-in {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: none; }
}

@keyframes radar-miss-shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-6px); }
    40% { transform: translateX(5px); }
    60% { transform: translateX(-3px); }
    80% { transform: translateX(2px); }
}

@keyframes radar-miss-glow {
    from { box-shadow: 0 0 0 4px var(--rl-color-signal-wash), 0 0 14px var(--rl-color-signal-wash); }
    to { box-shadow: 0 0 0 4px var(--rl-color-signal-wash), 0 0 28px var(--rl-color-signal-glow); }
}

@keyframes radar-live {
    0% { box-shadow: 0 0 0 0 var(--rl-color-found-glow); }
    100% { box-shadow: 0 0 0 6px transparent; }
}

@keyframes prize-nudge {
    0%, 100% { border-color: var(--rl-color-dialog-edge); box-shadow: none; }
    50% { border-color: var(--rl-color-signal-glow); box-shadow: 0 0 0 3px var(--rl-color-signal-wash); color: var(--rl-color-bone); }
}

@keyframes prize-title {
    0%, 100% { color: var(--rl-color-bone-subtle); }
    50% { color: var(--rl-color-bone); }
}

/*
 * Reduced motion: drop the ambient loops (idle sweep, pulses, pings, nudge).
 * Scan feedback (fast sweep, bars filling) stays, and the prize row keeps a
 * static red hint instead of the wave.
 */
@media (prefers-reduced-motion: reduce) {
    .radar-card__bar--on,
    .radar-card__dot--live,
    .prize-picker--prompt .prize-tile,
    .prize-picker--prompt .prize-picker__title {
        animation: none;
    }

    .radar-scan--missed,
    .radar-note {
        animation: none;
    }

    .prize-picker--prompt .prize-tile {
        border-color: var(--rl-color-signal-glow);
    }
}

@keyframes radar-signal {
    0% { opacity: 0.25; }
    100% { opacity: 1; }
}
</style>
