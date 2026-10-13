<script setup lang="ts">
/*
 * Add-to-Home-Screen prompt.
 *
 * Two channels — browsers behave differently:
 *   1. Android Chrome / Edge (HTTPS only): the browser dispatches
 *      `beforeinstallprompt` when the site qualifies as installable. We
 *      capture the event, pop our own banner, and on Accept call the
 *      native `prompt()` so the OS shows its install dialog. The user
 *      taps "Install" once and the icon is added with no further work.
 *      This is the maximum automation the platform allows.
 *   2. iOS Safari: Apple exposes NO programmatic install API at all.
 *      We cannot trigger Add-to-Home-Screen from JS. The only path is
 *      Share → Add to Home Screen, performed manually by the user.
 *      To make those steps obvious, the iOS branch shows an in-app
 *      illustrated guide (Share icon SVG + numbered steps) instead of
 *      a plain sentence. Already-installed standalone sessions hide
 *      the banner via the display-mode media query.
 *
 * Persistence: a "user said no" / "user installed" flag is stored in
 * localStorage so we never re-pester after the user makes a choice.
 *
 * Look: 2026 redesign (popup card, red / outlined buttons, steps numbered
 * like How to play). English only, so it stays left to right even over the
 * Arabic game screen.
 */
import { onMounted, onBeforeUnmount, ref, computed } from 'vue';

const STORAGE_KEY = 'radarleb.a2hs.dismissedAt';
const SUPPRESS_FOR_DAYS = 30;

const showBanner = ref(false);
const showIosGuide = ref(false);
const isIos = ref(false);
const installEvent = ref<any>(null);

const titleText = computed(() => 'Add RadarLeb to your home screen');
const bodyText = computed(() => isIos.value
    ? 'Quick access without typing the URL. Add a RadarLeb icon to your home screen in two taps.'
    : "Quick access without typing the URL. We'll place the RadarLeb icon on your home screen.");

function detectIosSafari(): boolean {
    if (typeof window === 'undefined') return false;
    const ua = window.navigator.userAgent || '';
    const isiPhoneOrIPad = /iPhone|iPad|iPod/i.test(ua);
    // Modern iPadOS reports as Mac — guard with touch points.
    const isIpadOnMac = ua.includes('Macintosh') && (navigator.maxTouchPoints || 0) > 1;
    if (!isiPhoneOrIPad && !isIpadOnMac) return false;
    // Exclude in-app webviews (Instagram, Facebook, etc.) where Add to Home
    // Screen does not exist.
    const isStandaloneSafari = /Safari/i.test(ua) && !/CriOS|FxiOS|EdgiOS|OPiOS|Instagram|FBAN|FBAV|Line/i.test(ua);
    return isStandaloneSafari;
}

function isStandaloneDisplay(): boolean {
    if (typeof window === 'undefined') return false;
    if (window.matchMedia?.('(display-mode: standalone)').matches) return true;
    // iOS legacy signal.
    return Boolean((window.navigator as any).standalone);
}

function recentlyDismissed(): boolean {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return false;
        const ts = parseInt(raw, 10);
        if (!Number.isFinite(ts)) return false;
        const ageMs = Date.now() - ts;
        return ageMs < SUPPRESS_FOR_DAYS * 24 * 60 * 60 * 1000;
    } catch {
        return false;
    }
}

function rememberDismissal() {
    try {
        window.localStorage.setItem(STORAGE_KEY, String(Date.now()));
    } catch { /* private mode — ignore */ }
}

function onBeforeInstallPrompt(e: Event) {
    e.preventDefault();
    installEvent.value = e;
    if (!recentlyDismissed() && !isStandaloneDisplay()) {
        showBanner.value = true;
    }
}

function onAppInstalled() {
    showBanner.value = false;
    installEvent.value = null;
    rememberDismissal();
}

async function accept() {
    if (installEvent.value && typeof installEvent.value.prompt === 'function') {
        try {
            installEvent.value.prompt();
            await installEvent.value.userChoice;
        } catch { /* user cancelled — non-fatal */ }
        installEvent.value = null;
    }
    showBanner.value = false;
    rememberDismissal();
}

function openIosGuide() {
    showIosGuide.value = true;
}

function closeIosGuide() {
    showIosGuide.value = false;
    showBanner.value = false;
    rememberDismissal();
}

function dismiss() {
    showBanner.value = false;
    showIosGuide.value = false;
    rememberDismissal();
}

onMounted(() => {
    if (typeof window === 'undefined') return;
    if (isStandaloneDisplay() || recentlyDismissed()) return;

    isIos.value = detectIosSafari();

    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt);
    window.addEventListener('appinstalled', onAppInstalled);

    if (isIos.value) {
        setTimeout(() => {
            if (!recentlyDismissed() && !isStandaloneDisplay()) {
                showBanner.value = true;
            }
        }, 2000);
    }
});

onBeforeUnmount(() => {
    if (typeof window === 'undefined') return;
    window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt);
    window.removeEventListener('appinstalled', onAppInstalled);
});
</script>

<template>
    <Teleport to="body">
        <!-- Compact prompt banner (initial) -->
        <div
            v-if="showBanner && !showIosGuide"
            class="a2hs-backdrop"
            role="dialog"
            aria-modal="true"
            aria-labelledby="a2hs-title"
            lang="en"
            dir="ltr"
        >
            <div class="a2hs-card">
                <img src="/android-chrome-192x192.png" alt="" class="a2hs-icon" width="52" height="52" />
                <div class="a2hs-copy">
                    <h3 id="a2hs-title" class="a2hs-title">{{ titleText }}</h3>
                    <p class="a2hs-body">{{ bodyText }}</p>
                </div>
                <div class="a2hs-actions">
                    <button type="button" class="a2hs-btn a2hs-btn--decline" @click="dismiss">
                        Not now
                    </button>
                    <button v-if="!isIos" type="button" class="a2hs-btn a2hs-btn--accept" @click="accept">
                        Add to Home Screen
                    </button>
                    <button v-else type="button" class="a2hs-btn a2hs-btn--accept" @click="openIosGuide">
                        Show me how
                    </button>
                </div>
            </div>
        </div>

        <!--
          iOS visual guide. Apple does not expose any install API, so this is
          the closest we can get to automation: a clear three-step illustration
          users can follow without leaving the page. The Share icon SVG is
          inlined so it renders identically on every iOS version.
        -->
        <div
            v-if="showIosGuide"
            class="a2hs-backdrop a2hs-backdrop--full"
            role="dialog"
            aria-modal="true"
            aria-labelledby="a2hs-guide-title"
            lang="en"
            dir="ltr"
        >
            <div class="a2hs-guide">
                <h3 id="a2hs-guide-title" class="a2hs-guide__title">Add RadarLeb to your iPhone home screen</h3>
                <ol class="a2hs-steps">
                    <li class="a2hs-step">
                        <span class="a2hs-step__num">01</span>
                        <div class="a2hs-step__body">
                            <span>Tap the <strong>Share</strong> button at the bottom of Safari.</span>
                            <svg class="a2hs-share-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3v12M12 3l-4 4M12 3l4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                            </svg>
                        </div>
                    </li>
                    <li class="a2hs-step">
                        <span class="a2hs-step__num">02</span>
                        <div class="a2hs-step__body">
                            Scroll down and tap <strong>“Add to Home Screen”</strong>.
                        </div>
                    </li>
                    <li class="a2hs-step">
                        <span class="a2hs-step__num">03</span>
                        <div class="a2hs-step__body">
                            Tap <strong>Add</strong>. The RadarLeb icon will appear on your home screen.
                        </div>
                    </li>
                </ol>
                <p class="a2hs-note">
                    Apple does not let websites add the icon automatically — these
                    two taps are the only way on iPhone.
                </p>
                <button type="button" class="a2hs-btn a2hs-btn--accept a2hs-done" @click="closeIosGuide">
                    Done
                </button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Banner: a popup card pinned to the bottom, clear of the home bar and the notch. */
.a2hs-backdrop {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1100;
    display: flex;
    justify-content: center;
    padding: 12px;
    padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));
    padding-left: calc(12px + env(safe-area-inset-left, 0px));
    padding-right: calc(12px + env(safe-area-inset-right, 0px));
    font-family: var(--rl-font-sans);
    pointer-events: none;
}

.a2hs-backdrop--full {
    inset: 0;
    align-items: center;
    padding: 24px;
    background: var(--rl-color-dialog-backdrop);
    pointer-events: auto;
}

.a2hs-card {
    pointer-events: auto;
    display: grid;
    grid-template-columns: 52px minmax(0, 1fr);
    gap: 14px 14px;
    box-sizing: border-box;
    width: 100%;
    max-width: 26rem;
    padding: 16px;
    border: 1px solid var(--rl-color-dialog-edge);
    border-radius: 18px;
    background: var(--rl-color-night-raised);
    color: var(--rl-color-bone);
    box-shadow: 0 14px 40px rgba(0, 0, 0, 0.55);
    animation: a2hs-up 260ms ease-out;
}

.a2hs-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    object-fit: cover;
}

.a2hs-title {
    margin: 0 0 4px;
    font-family: var(--rl-font-headline);
    font-weight: 800;
    font-size: 20px;
    line-height: 1.05;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: var(--rl-color-bone);
}

.a2hs-body {
    margin: 0;
    font-size: 14px;
    line-height: 1.45;
    color: var(--rl-color-dialog-text);
}

.a2hs-actions {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

/* Same look as the redesign's buttons, sized for a banner. */
.a2hs-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 46px;
    padding: 0 10px;
    border: 0;
    border-radius: var(--rl-radius-button);
    background: var(--rl-color-signal);
    color: #ffffff;
    font-family: var(--rl-font-headline);
    font-weight: 800;
    font-size: 17px;
    line-height: 1.05;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    transition: transform 220ms ease-out, filter 220ms ease-out, background-color 220ms ease-out;
}

.a2hs-btn:active {
    transform: scale(0.97);
    filter: brightness(1.12);
    transition-duration: 60ms;
}

.a2hs-btn:focus-visible {
    outline: 2px solid var(--rl-color-bone);
    outline-offset: 2px;
}

.a2hs-btn--decline {
    border: 1px solid var(--rl-color-night-line);
    background: transparent;
    color: var(--rl-color-bone);
}

.a2hs-btn--decline:active {
    background: var(--rl-color-press-wash);
    filter: none;
}

/* iOS guide: the redesign popup card. */
.a2hs-guide {
    box-sizing: border-box;
    width: 100%;
    max-width: 24rem;
    max-height: calc(100svh - 48px);
    overflow-y: auto;
    padding: 28px 22px 22px;
    border: 1px solid var(--rl-color-dialog-edge);
    border-radius: 22px;
    background: var(--rl-color-night-raised);
    color: var(--rl-color-bone);
    animation: a2hs-pop 220ms ease-out;
}

.a2hs-guide__title {
    margin: 0 0 20px;
    font-family: var(--rl-font-headline);
    font-weight: 800;
    font-size: 28px;
    line-height: 1.05;
    letter-spacing: 0.02em;
    text-align: center;
    text-transform: uppercase;
    color: var(--rl-color-bone);
}

.a2hs-steps {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin: 0;
    padding: 0;
    list-style: none;
}

/* Numbered like How to play: red mono 01 / 02 / 03. */
.a2hs-step {
    display: grid;
    grid-template-columns: 28px minmax(0, 1fr);
    gap: 10px;
    font-size: 15px;
    line-height: 1.55;
    color: var(--rl-color-bone-soft);
}

.a2hs-step strong {
    font-weight: 600;
    color: var(--rl-color-bone);
}

.a2hs-step__num {
    padding-top: 2px;
    font-family: var(--rl-font-mono);
    font-size: 13px;
    color: var(--rl-color-alert);
}

/* Normal text flow, so bold words stay inside their sentence; the Share icon sits at the end. */
.a2hs-step__body {
    display: block;
}

.a2hs-share-icon {
    display: inline-block;
    width: 20px;
    height: 20px;
    margin-left: 6px;
    vertical-align: -4px;
    color: var(--rl-color-bone);
}

.a2hs-note {
    margin: 18px 0 0;
    font-size: 13px;
    line-height: 1.5;
    text-align: center;
    color: var(--rl-color-bone-subtle);
}

.a2hs-done {
    width: 100%;
    min-height: 54px;
    margin-top: 20px;
    font-size: 21px;
}

@keyframes a2hs-up {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: none; }
}

@keyframes a2hs-pop {
    from { opacity: 0; transform: scale(0.96); }
    to { opacity: 1; transform: none; }
}

@media (prefers-reduced-motion: reduce) {
    .a2hs-card,
    .a2hs-guide {
        animation: none;
    }
}

@media (display-mode: standalone) {
    .a2hs-backdrop { display: none !important; }
}
</style>
