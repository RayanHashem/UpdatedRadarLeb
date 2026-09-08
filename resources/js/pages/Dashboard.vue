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

        <div v-if="activeOverlay" class="overlay" @click.self="activeOverlay = null">

             <div class="overlay-content">
                <!--
                  Help + Winners overlays — extracted to dedicated components
                  (resources/js/components/dashboard/{HelpOverlay,WinnersOverlay}.vue)
                  so this page file isn't carrying their template + their copy.
                  Settings overlay stays inline below because it's intertwined
                  with the page's password-form state.
                -->
                <HelpOverlay v-if="activeOverlay === 'help'" @close="activeOverlay = null" />

                <WinnersOverlay v-else-if="activeOverlay === 'winners'" @close="activeOverlay = null" />

                <template v-else-if="activeOverlay === 'settings'">
                    <div class="settings-container">
                        <h1 class="overlay-title">SETTINGS</h1>
                        <div v-if="showSuccessMessage" class="success-message" style="background-color: #5DB0A1; color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
                            Password changed successfully
                        </div>
                        <div v-if="currentPage === 'settings'" class="settings-menu">
                            <!-- Button class updated to reflect musicOn state -->
                            <button :class="audioEnabled ? 'a-btn a-btn-music-on' : 'a-btn a-btn-music-off'" @click="toggleMusic">
                                Music
                            </button>

                            <button class="a-btn a-btn-default" @click="currentPage = 'password'">
                                Change Password
                            </button>

                            <button type="button" class="a-btn a-btn-default" @click="handleLogout">
                                Log out
                            </button>

                            <!-- Close the Settings overlay from the main page too (mobile UX). -->
                            <button class="a-btn a-btn-default overlay-back-btn" @click="activeOverlay = null">
                                Back
                            </button>
                        </div>

                        <div v-else-if="currentPage === 'password'" class="settings-menu">
                            <!-- Step A: Verify old password -->
                            <div v-if="!oldPasswordVerified">
                                <h2 class="overlay-subtitle">Change Your Password</h2>
                                <input 
                                    type="password" 
                                    placeholder="Enter old password" 
                                    v-model="oldPassword" 
                                    class="a-input"
                                    :class="{ 'error': passwordError }"
                                />
                                <div v-if="passwordError" class="error-message" style="color: #ef4444; margin-top: 10px; font-size: 0.9em; display: flex; align-items: flex-start; gap: 8px;">
                                    <span style="font-size: 16px; flex-shrink: 0; margin-top: 2px;">⚠️</span>
                                    <span>{{ passwordError }}</span>
                                </div>
                                <div v-if="wrongAttempts >= 3" class="mt-3">
                                    <Link
                                        as="button"
                                        type="button"
                                        href="/forgot-password"
                                        class="a-btn a-btn-default"
                                        style="width: 100%;"
                                    >
                                        Forgot Password?
                                    </Link>
                                </div>
                                <button 
                                    class="a-btn a-btn-music-on mt-3" 
                                    @click="verifyOldPassword"
                                    :disabled="!oldPassword || verifyingPassword"
                                    style="width: 100%;"
                                >
                                    {{ verifyingPassword ? 'Verifying...' : 'Continue' }}
                                </button>
                                <button 
                                    class="a-btn a-btn-default mt-2" 
                                    @click="resetPasswordFlow"
                                    style="width: 100%;"
                                >
                                    {{ t('common.back') }}
                                </button>
                            </div>

                            <!-- Step B: Enter new password -->
                            <div v-else>
                                <h2 class="overlay-subtitle">Enter New Password</h2>
                                <input 
                                    type="password" 
                                    placeholder="Enter new password" 
                                    v-model="newPassword" 
                                    class="a-input"
                                    :class="{ 'error': newPasswordError }"
                                />
                                <div v-if="newPasswordError" class="error-message" style="color: #ef4444; margin-top: 10px; font-size: 0.9em; display: flex; align-items: flex-start; gap: 8px;">
                                    <span style="font-size: 16px; flex-shrink: 0; margin-top: 2px;">⚠️</span>
                                    <span>{{ newPasswordError }}</span>
                                </div>
                                <input 
                                    type="password" 
                                    placeholder="Confirm new password" 
                                    v-model="confirmPassword" 
                                    class="a-input mt-3"
                                    :class="{ 'error': confirmPasswordError }"
                                />
                                <div v-if="confirmPasswordError" class="error-message" style="color: #ef4444; margin-top: 10px; font-size: 0.9em; display: flex; align-items: flex-start; gap: 8px;">
                                    <span style="font-size: 16px; flex-shrink: 0; margin-top: 2px;">⚠️</span>
                                    <span>{{ confirmPasswordError }}</span>
                                </div>
                                <button 
                                    class="a-btn a-btn-music-on mt-3" 
                                    @click="updatePassword"
                                    :disabled="!newPassword || !confirmPassword || updatingPassword"
                                    style="width: 100%;"
                                >
                                    {{ updatingPassword ? 'Updating...' : 'Done' }}
                                </button>
                                <button 
                                    class="a-btn a-btn-default mt-2" 
                                    @click="resetPasswordFlow"
                                    style="width: 100%;"
                                >
                                    {{ t('common.back') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>


        <section v-if="loading" id="loading-game">
            <div class="loader-content">
                <div class="loader-circle-container">
                    <img src="/assets/imgs/Flag_of_Lebanon.png" class="flag-center" alt="Lebanon Flag" />
                    <svg class="loader-svg" viewBox="0 0 100 100">
                        <!-- Add this <defs> block here -->
                        <defs>
                            <linearGradient id="loaderGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#48fdd0" />
                                <stop offset="100%" stop-color="#62c3ff" />
                            </linearGradient>
                        </defs>
                        <circle class="loader-bg" cx="50" cy="50" r="45"></circle>
                        <circle class="loader-progress" cx="50" cy="50" r="45"></circle>
                    </svg>

                </div>
            </div>
        </section>

        <section v-show="!loading" id="game">
            <!--
              Page background video. Two mobile-specific notes:
                * `muted` is a plain HTML attribute (not `:muted="true"`)
                  so iOS Safari sees it at parse time and honours
                  `autoplay`. The Vue-bound form sets the *property*
                  after mount, which iOS sometimes treats as "user just
                  unmuted, don't autoplay".
                * `disablePictureInPicture` + `disableRemotePlayback`
                  stop iOS from offering PiP / AirPlay which would
                  pause the in-page playback when accidentally tapped.
              Resume guard for the rare cases iOS still pauses (lock
              screen, Low Power Mode, incoming call, tab switched away
              and back) lives in onMounted as `keepBgVideoPlaying`.
            -->
            <video autoplay
                   muted
                   loop playsinline
                   disablepictureinpicture
                   disableremoteplayback
                   id="myVideo"
                   preload="auto">
                <source src="/assets/imgs/vid.webm" type="video/webm">
                Your browser does not support HTML5 video.
            </video>

            <div class="game-content-wrapper">
                <div class="bar">
                    <div class="bar-left">
                        <img src="/assets/imgs/logo.png" class="logo-nav">
                    </div>
                    <div class="bar-right">
                        <!--
                          RADAR CASH balance chip. We removed the visible "RADAR CASH N"
                          label under the store icon (Figma "Main Page" shows only the
                          tile, no label) — but the user still needs to see their balance
                          at a glance. Surface it here as a small pill next to the locale
                          switcher. Same source-of-truth (`walletBalance` ref) as before;
                          updates automatically when a scan completes or the wallet is
                          topped up.
                        -->
                        <div class="bar-balance" :title="`Radar cash: ${walletBalance}`" aria-label="Radar cash balance">
                            <img src="/assets/imgs/radar-cash.png" class="bar-balance__icon" alt="" />
                            <span class="bar-balance__value">{{ walletBalance }}</span>
                        </div>

                        <!--
                          Locale switcher (EN/AR) lives leftmost in the right cluster — it's
                          meta-action (changes the whole UI language) so it sits visually
                          separated from the navigation icons by the .bar-right gap.
                        -->
                        <div class="bar-locale">
                            <LocaleSwitcher />
                        </div>

                        <!--
                          Top-bar menu icons were previously bare <img @click>, which on mobile
                          sometimes registered as a drag (finger jitter) instead of a tap, felt
                          unresponsive, and was not reachable by assistive tech. They are now
                          proper <button> elements with aria-labels and ≥44x44 tap targets via
                          the .menu-item-btn CSS rule — the image is a child with pointer-events
                          disabled so the tap always lands on the button.
                        -->
                        <button type="button" class="menu-item-btn" aria-label="Winners" @click="openOverlay('winners')">
                            <img src="/assets/imgs/winners-button.png" class="menu-item" alt="" />
                            <span class="menu-item-label">Winners</span>
                        </button>
                        <button type="button" class="menu-item-btn" aria-label="Help" @click="openOverlay('help')">
                            <img src="/assets/imgs/help-button.png" class="menu-item" alt="" />
                            <span class="menu-item-label">Help</span>
                        </button>
                        <button type="button" class="menu-item-btn" aria-label="Settings" @click="openOverlay('settings')">
                            <img src="/assets/imgs/settings-button.png" class="menu-item" alt="" />
                            <span class="menu-item-label">Settings</span>
                        </button>
                    </div>
                </div>

                <div class="radar-row">
                    <div class="col-3 antenna-detection-col">
                        <div class="icon-box">
                              <img :src="antennaIconSrc" class="an" style="width: clamp(50px, 15vw, 100px); margin-bottom: 0px; max-width: 100%;"/>
                              <div class="antenna-label">ANTENNA DETECTION</div>
                        </div>
                        <div class="bar-container">
                            <div class="fill-bar" :style="{ height: (visibleCount / originalColors.length) * 100 + '%' }"></div>
                        </div>
                    </div>

                    <div class="col-6 radar-col" style="padding:0px;">
                        <div class="radar" :class="{ 'radar--scanning': scanning }">
                            <!--
                              Idle: ellipse.png in the center; on Scan it hides and
                              radar.webm shows. Video stays mounted for cache; v-show
                              + explicit play/pause from startScan() (no autoplay race).
                            -->
                            <img
                                v-show="!scanning"
                                id="radarIdleEllipse"
                                src="/assets/imgs/ellipse.png"
                                alt=""
                                class="radar-center-asset"
                                width="800"
                                height="800"
                                decoding="async"
                            />
                            <video v-show="scanning" muted loop playsinline id="myVideo2" ref="radarVideo" class="radar-center-asset" preload="auto">
                                <source v-if="videoSrcsReady" src="/assets/imgs/radar.webm" type="video/webm" />
                                Your browser does not support HTML5 video.
                            </video>
                        </div>
                    </div>

                    <div class="col-3 prize-selection-col" style="padding:0 !important;">
                        <div id="image-selector" class="prize-selector">
                            <div
                                v-for="(config, index) in PRIZE_DISPLAY_CONFIG"
                                :key="index"
                                class="prize-item"
                                :class="{
                                    'prize-item--selected':
                                        orderedPrizes[index] && selectedGameId === orderedPrizes[index].id,
                                }"
                                role="button"
                                tabindex="0"
                                @click="onPrizeSlotClick(index)"
                                @keydown.enter.prevent="onPrizeSlotClick(index)"
                                @keydown.space.prevent="onPrizeSlotClick(index)"
                            >
                                <img
                                    class="selectable"
                                    :src="(orderedPrizes[index] && selectedGameId === orderedPrizes[index].id) ? config.detectedImg : config.img"
                                    :alt="config.label"
                                />
                                <div class="prize-label">{{ config.label }}</div>
                                <div class="prize-price">{{ config.price }}</div>
                            </div>
                        </div>
                    </div>
                </div>







                <div class="antenna-container">
                    <div v-for="n in 6" :key="n">
                        <img
                            :src="n <= currentProgress.radar_level ? '/assets/imgs/enable1.png' : '/assets/imgs/enable.png'"
                            class="antenna-icon"
                        />
                    </div>
                </div>






                <!--
                  Bottom action row — matches Figma "Main Page": three controls
                  on a baseline. Left and right are square cyan tile buttons
                  (store, my-location); the center is a wide pill SCAN button.
                  The side icons carry compact labels directly underneath.
                -->
                <div class="button-row">
                    <div class="cash-balance-container button-row__side button-row__side--lead">
                        <img class="cash-balance-img" src="/assets/imgs/radar-cash.png" alt="Store">
                        <span class="button-row__label">Radar Cash</span>
                        <span class="visually-hidden">Radar cash {{ walletBalance }}</span>
                    </div>
                    <div class="button-row__center">
                        <button id="scan" class="btn btn-custom" :disabled="!canScan" :style="buttonStyle" @click="startScan">
                            {{ buttonText }}
                        </button>
                    </div>
                    <div class="location-container button-row__side button-row__side--end">
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
                            class="location-button"
                            aria-label="Share my location"
                            @click.prevent="onLocationTap"
                        >
                            <img class="location-button-img" src="/assets/imgs/my-location.png" alt="">
                        </button>
                        <span class="button-row__label">My Location</span>
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
import { router, Link, usePage } from '@inertiajs/vue3';
import GameModal from '@/components/GameModal.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import HelpOverlay from '@/components/dashboard/HelpOverlay.vue';
import WinnersOverlay from '@/components/dashboard/WinnersOverlay.vue';
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
import { getPrizeRules, getMinDepositMessage, getMinDepositMessageBySlot } from '@/lib/prizeRules.js';
import { useTranslate } from '@/composables/useTranslate';

// i18n bindings — `t` is still used for the (still-inline) settings overlay
// and a few one-off strings on the page. The Help and Winners overlays now
// own their own translation lookups internally.
const { t } = useTranslate();

const PRIZE_DISPLAY_ORDER = ['Mobile', 'Bike & Electronics', 'SUV', 'Muscle Car', 'Super Car'];

/** Display config for each prize slot (same order as orderedPrizes). Enables Vue @click instead of DOM listeners. */
const PRIZE_DISPLAY_CONFIG = [
    { img: '/assets/imgs/mobile1.png', detectedImg: '/assets/imgs/mobile-detected1.png', label: 'MOBILE', price: '1.500$' },
    { img: '/assets/imgs/be1.png', detectedImg: '/assets/imgs/be-detected1.png', label: 'BIKE / ELECTRONICS', price: '15.000$' },
    { img: '/assets/imgs/suv1.png', detectedImg: '/assets/imgs/suv-detected1.png', label: 'SUV', price: '50.000$' },
    { img: '/assets/imgs/muscle-car1.png', detectedImg: '/assets/imgs/muscle-car-detected1.png', label: 'MUSCLE CAR', price: '150.000$' },
    { img: '/assets/imgs/super-car1.png', detectedImg: '/assets/imgs/super-car-detected1.png', label: 'SUPER CAR', price: '200.000$' },
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

const buttonText = computed(() => {
    if (!selectedGameEnabled.value) return 'Coming soon';
    if (scanning.value) return 'Scanning…';
    if (detectionStatus.value === 'found') return 'Antenna detected';
    if (detectionStatus.value === 'not-found') return 'Antenna not detected';
    return 'Scan';
});

const buttonStyle = computed(() => {
    if (scanning.value) {
        return { backgroundColor: '#E25669', color: '#fff' }; // Red color
    } else if (detectionStatus.value === 'not-found') {
        return { backgroundColor: '#E25669', color: '#fff' };
    } else {
        return { backgroundColor: '#66afdb', color: '#fff' }; // Example default blue
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
    gameModalSecondaryLabel.value = config.secondaryLabel ?? 'Close';
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
    const label = (prizeName && String(prizeName).trim()) || 'prize';
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
        title: 'You won!',
        message: `Congratulations on winning the ${label} Prize!`,
        subtext: 'Our team will reach out shortly with the details.',
        primaryLabel: '',
        primaryAction: '',
        secondaryLabel: 'Awesome',
    });
}

/*
 * Per-antenna progress popup. Fires after every successful detection
 * EXCEPT the 6th (which gets the full celebrateWin popup instead). Mirrors
 * the radar_level the server just returned, so it always matches what the
 * user actually has — no client-side counter to drift out of sync.
 */
function showAntennaProgressModal(level, prizeName) {
    const label = (prizeName && String(prizeName).trim()) || 'prize';
    const countLine = `Antenna count: ${level}/6`;
    const message = level === 5
        ? `${countLine} — Almost there! Just one more!`
        : countLine;
    showGameModal({
        title: 'Antenna Detected!',
        message,
        subtext: `Keep playing to acquire all 6 antennas and win this ${label}!`,
        primaryLabel: '',
        primaryAction: '',
        secondaryLabel: 'Continue Playing',
    });
}

function showMinDepositModal(message) {
    showGameModal({
        title: 'Minimum deposit required',
        message,
        primaryLabel: 'How to play',
        primaryAction: 'openHelp',
        secondaryLabel: 'Cancel',
    });
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
        showMinDepositModal(getMinDepositMessageBySlot(index));
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
            title: 'Coming soon',
            message: 'Coming soon! Check out our other prizes for now!',
            primaryLabel: '',
            primaryAction: '',
            secondaryLabel: 'Close',
        });
        return;
    }

    const rules = getPrizeRules(prize.name);
    const balance = Number(walletBalance.value) || 0;
    const scanCost = rules?.scanCostRadars ?? 0;

    if (balance < scanCost) {
        const message = getMinDepositMessage(prize.name) || `You need to deposit at least ${rules?.minDepositDollars ?? 0}$ for a chance to win this prize.`;
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
                title: 'Coming soon',
                message: 'Coming soon! Check out our other prizes for now!',
                primaryLabel: '',
                primaryAction: '',
                secondaryLabel: 'Close',
            });
            return;
        }
        console.error('Failed to persist prize selection:', error);
    });
}
const currentProgress = computed(() => {
    const g = prizes.value.find(p => p.id == selectedGameId.value)

    return g?.progress ?? { radar_level: 0 }
})

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
            title: 'Select a prize first',
            message: 'You need to select a prize first and add Radar Cash before you can scan.',
            primaryLabel: 'How to play',
            primaryAction: 'openHelp',
            secondaryLabel: 'OK',
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
        const message =
            getMinDepositMessage(prize.name)
            || `You need to deposit at least ${rules.minDepositDollars}$ for a chance to win this prize.`;
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
                title: 'Coming soon',
                message: 'Coming soon! Check out our other prizes for now!',
                primaryLabel: '',
                primaryAction: '',
            });
            return;
        }

        if (status === 402) {
            scanning.value = false;
            detectionStatus.value = 'idle';
            pauseRadarVideo();
            const message =
                getMinDepositMessage(prize?.name)
                || (rules ? `You need to deposit at least ${rules.minDepositDollars}$ for a chance to win this prize.` : 'You need to deposit before playing for this prize.');
            showMinDepositModal(message);
            return;
        }

        if (status === 423) {
            scanning.value = false;
            detectionStatus.value = 'idle';
            pauseRadarVideo();
            showGameModal({
                title: 'Radar offline',
                message: 'The radar is temporarily offline. Please try again in a moment.',
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
        passwordError.value = 'Please enter your old password';
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
                passwordError.value = 'Too many incorrect attempts. Please use "Forgot Password?" to reset.';
            } else {
                passwordError.value = `Incorrect password. ${3 - wrongAttempts.value} attempts remaining.`;
            }
        }
    } catch (error) {
        if (error.response?.data?.message) {
            passwordError.value = error.response.data.message;
        } else {
            passwordError.value = 'An error occurred. Please try again.';
        }
        wrongAttempts.value++;
        if (wrongAttempts.value >= 3) {
            passwordError.value = 'Too many incorrect attempts. Please use "Forgot Password?" to reset.';
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
        newPasswordError.value = 'Please enter a new password';
        return;
    }

    if (newPassword.value.length < 8) {
        newPasswordError.value = 'Password must be at least 8 characters';
        return;
    }

    if (!confirmPassword.value) {
        confirmPasswordError.value = 'Please confirm your new password';
        return;
    }

    if (newPassword.value !== confirmPassword.value) {
        confirmPasswordError.value = 'Passwords do not match';
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
            passwordError.value = 'An error occurred. Please try again.';
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
        showLocationError('Location unavailable', 'Geolocation is not supported by your browser.');
        playClickSound();
        return;
    }

    if (typeof window !== 'undefined' && !window.isSecureContext) {
        showLocationError('Location unavailable', 'Location access requires HTTPS. Please open this page via https://');
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
            title: 'Location found',
            message: 'Open your location in Google Maps.',
            primaryLabel: 'Open Map',
            primaryRoute: locationUrl.value,
            primaryNewTab: true,
        });
    };

    const onFinalError = (error) => {
        locationRequestInProgress.value = false;
        const msg =
            error.code === 1
                ? 'Location access is required to use this feature. Please enable it in your browser settings.'
                : 'Unable to get your location. Please make sure location services are on and try again.';
        showLocationError('Location unavailable', msg);
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

#loading-game {
    background-image: url("/assets/imgs/loading-bg.png");
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 1000;
    display: flex;
    justify-content: center;
    align-items: center;
    background-size: cover;
    background-position: center;
}

.loader-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    position: relative;
}

.loader-circle-container {
    position: relative;
    width: 500px; /* Adjust size as needed */
    height: 500px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
}

.loader-svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.loader-bg {
    fill: none;
    stroke: rgba(255, 255, 255, 0.3); /* Lighter grey for background circle */
    stroke-width: 5;
}

.loader-progress {
    fill: none;
    stroke: url(#loaderGradient); /* Reference the gradient defined in SVG */
    stroke-width: 8;
    stroke-linecap: round;
    animation: load-progress 2s linear forwards; /* 2 seconds animation */
    stroke-dasharray: 282.7; /* 2 * PI * 45 (radius) */
    stroke-dashoffset: 282.7; /* Start fully hidden */
    filter: drop-shadow(0 0 5px rgba(0, 255, 255, 0.7)) drop-shadow(0 0 5px rgba(0, 255, 255, 0.5)); /* Neon glow effect */
}

@keyframes load-progress {
    0% {
        stroke-dashoffset: 282.7;
    }
    100% {
        stroke-dashoffset: 0;
    }
}

.flag-center {
    width: 60%; /* Adjust size of the flag */
    height: 60%;
    object-fit: contain;
    position: absolute;
    border-radius: 50%; /* Make it circular */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5); /* Optional: add a subtle shadow */
}

.radar-text-overlay {
    position: absolute;
    display: flex;
    flex-direction: column;
    align-items: center;
    color: white;
    font-family: 'Bungee', cursive; /* Use Bungee for the main title */
    text-align: center;
    text-shadow: 0 0 10px rgba(0, 255, 255, 0.8), 0 0 20px rgba(0, 255, 255, 0.6); /* Neon text shadow */
}

.radar-title {
    font-size: 2.2em; /* Adjust size */
    line-height: 1;
}

.port-text {
    font-family: 'GE Flow', sans-serif; /* Use GE Flow for the subtitle */
    font-size: 0.9em; /* Adjust size */
    margin-top: 5px;
}

/* Responsive adjustments for the loader */
@media (max-width: 768px) {
    .loader-circle-container {
        width: 350px;
        height: 350px;
    }
    .radar-title {
        font-size: 1.8em;
    }
    .port-text {
        font-size: 0.7em;
    }
    .loader-progress {
        stroke-width: 6;
    }
}

@media (max-width: 480px) {
    .loader-circle-container {
        width: 280px;
        height: 280px;
    }
    .radar-title {
        font-size: 1.5em;
    }
    .port-text {
        font-size: 0.6em;
    }
    .loader-progress {
        stroke-width: 5;
    }
}

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
