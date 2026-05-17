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
        >
            <div class="a2hs-card">
                <img src="/android-chrome-192x192.png" alt="" class="a2hs-icon" />
                <div class="a2hs-copy">
                    <h3 id="a2hs-title" class="a2hs-title">{{ titleText }}</h3>
                    <p class="a2hs-body">{{ bodyText }}</p>
                </div>
                <div class="a2hs-actions">
                    <button v-if="!isIos" type="button" class="a2hs-btn a2hs-btn--accept" @click="accept">
                        Add to Home Screen
                    </button>
                    <button v-else type="button" class="a2hs-btn a2hs-btn--accept" @click="openIosGuide">
                        Show me how
                    </button>
                    <button type="button" class="a2hs-btn a2hs-btn--decline" @click="dismiss">
                        Not now
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
        >
            <div class="a2hs-guide">
                <h3 id="a2hs-guide-title" class="a2hs-guide__title">Add RadarLeb to your iPhone home screen</h3>
                <ol class="a2hs-steps">
                    <li class="a2hs-step">
                        <span class="a2hs-step__num">1</span>
                        <div class="a2hs-step__body">
                            <span>Tap the <strong>Share</strong> button at the bottom of Safari.</span>
                            <svg class="a2hs-share-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3v12M12 3l-4 4M12 3l4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                            </svg>
                        </div>
                    </li>
                    <li class="a2hs-step">
                        <span class="a2hs-step__num">2</span>
                        <div class="a2hs-step__body">
                            Scroll down and tap <strong>“Add to Home Screen”</strong>.
                        </div>
                    </li>
                    <li class="a2hs-step">
                        <span class="a2hs-step__num">3</span>
                        <div class="a2hs-step__body">
                            Tap <strong>Add</strong>. The RadarLeb icon will appear on your home screen.
                        </div>
                    </li>
                </ol>
                <p class="a2hs-note">
                    Apple does not let websites add the icon automatically — these
                    two taps are the only way on iPhone.
                </p>
                <div class="a2hs-actions a2hs-actions--center">
                    <button type="button" class="a2hs-btn a2hs-btn--accept" @click="closeIosGuide">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.a2hs-backdrop {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1100;
    display: flex;
    justify-content: center;
    padding: 0.75rem;
    pointer-events: none;
}

.a2hs-backdrop--full {
    inset: 0;
    align-items: center;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    pointer-events: auto;
}

.a2hs-card {
    pointer-events: auto;
    width: 100%;
    max-width: 26rem;
    display: grid;
    grid-template-columns: 56px minmax(0, 1fr);
    gap: 0.75rem 0.85rem;
    padding: 0.85rem 1rem;
    background: rgba(6, 33, 46, 0.95);
    color: #ffffff;
    border: 1px solid rgba(98, 195, 255, 0.25);
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.a2hs-icon {
    grid-row: 1 / span 2;
    width: 56px;
    height: 56px;
    border-radius: 12px;
    object-fit: cover;
    align-self: start;
    background: rgba(255, 255, 255, 0.05);
}

.a2hs-title {
    margin: 0 0 0.2rem;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.01em;
}

.a2hs-body {
    margin: 0;
    font-size: 0.78rem;
    line-height: 1.4;
    color: rgba(255, 255, 255, 0.85);
}

.a2hs-actions {
    grid-column: 1 / span 2;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    flex-wrap: wrap;
    margin-top: 0.25rem;
}

.a2hs-actions--center {
    justify-content: center;
    margin-top: 0.5rem;
}

.a2hs-btn {
    border: none;
    border-radius: 999px;
    padding: 0.5rem 0.95rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: filter 120ms ease;
}

.a2hs-btn:hover {
    filter: brightness(1.07);
}

.a2hs-btn--accept {
    background-color: #62c3ff;
    color: #06212e;
}

.a2hs-btn--decline {
    background-color: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.18);
}

/* iOS guide modal */
.a2hs-guide {
    width: 100%;
    max-width: 22rem;
    background: rgba(6, 33, 46, 0.97);
    border: 1px solid rgba(98, 195, 255, 0.25);
    border-radius: 16px;
    padding: 1.1rem 1rem;
    color: #ffffff;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.a2hs-guide__title {
    margin: 0 0 0.85rem;
    font-size: 1rem;
    font-weight: 700;
    text-align: center;
    letter-spacing: 0.01em;
}

.a2hs-steps {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.a2hs-step {
    display: grid;
    grid-template-columns: 28px minmax(0, 1fr);
    gap: 0.6rem;
    align-items: start;
    font-size: 0.85rem;
    line-height: 1.4;
    color: rgba(255, 255, 255, 0.92);
}

.a2hs-step__num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background-color: #62c3ff;
    color: #06212e;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}

.a2hs-step__body {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.a2hs-share-icon {
    width: 22px;
    height: 22px;
    color: #62c3ff;
    flex-shrink: 0;
}

.a2hs-note {
    margin: 0.85rem 0 0;
    font-size: 0.72rem;
    line-height: 1.4;
    color: rgba(255, 255, 255, 0.65);
    text-align: center;
}

@media (display-mode: standalone) {
    .a2hs-backdrop { display: none !important; }
}
</style>
