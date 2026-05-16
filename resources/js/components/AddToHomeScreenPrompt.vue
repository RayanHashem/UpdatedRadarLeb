<script setup lang="ts">
/*
 * Add-to-Home-Screen prompt.
 *
 * Two channels — browsers behave differently:
 *   1. Android Chrome / Edge: the browser dispatches a `beforeinstallprompt`
 *      event when the site qualifies as installable. We capture the event,
 *      pop our own custom banner asking the user, and trigger `prompt()`
 *      only on accept. Declining (or our own "Not now") swallows the
 *      event so the browser's automatic mini-infobar doesn't reappear
 *      every visit.
 *   2. iOS Safari: no install API exists. The user must tap Share → Add
 *      to Home Screen manually. We show the same banner with iOS-tailored
 *      copy ("Tap the Share button, then 'Add to Home Screen'") so the
 *      user always has a path forward. Already-installed standalone
 *      sessions hide the banner via the display-mode media query.
 *
 * Persistence: a "user said no" / "user installed" flag is stored in
 * localStorage so we never re-pester after the user makes a choice.
 * Cleared automatically on cache wipe — that's fine.
 */
import { onMounted, onBeforeUnmount, ref, computed } from 'vue';

const STORAGE_KEY = 'radarleb.a2hs.dismissedAt';
const SUPPRESS_FOR_DAYS = 30;

const showBanner = ref(false);
const isIos = ref(false);
const installEvent = ref<any>(null);

const titleText = computed(() => 'Add RadarLeb to your home screen');
const bodyText = computed(() => isIos.value
    ? "Quick access without typing the URL. Tap the Share button below, then choose \"Add to Home Screen\"."
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
    // The browser fired the install — stop nagging.
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

function dismiss() {
    showBanner.value = false;
    rememberDismissal();
}

onMounted(() => {
    if (typeof window === 'undefined') return;
    if (isStandaloneDisplay() || recentlyDismissed()) return;

    isIos.value = detectIosSafari();

    // Android / Chrome path.
    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt);
    window.addEventListener('appinstalled', onAppInstalled);

    // iOS path: no event ever fires, just show the banner once mounted on
    // an actual iOS Safari session. Defer slightly so the banner doesn't
    // race the page paint.
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
        <div v-if="showBanner" class="a2hs-backdrop" role="dialog" aria-modal="true" aria-labelledby="a2hs-title">
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
                    <button v-else type="button" class="a2hs-btn a2hs-btn--accept" @click="dismiss">
                        Got it
                    </button>
                    <button type="button" class="a2hs-btn a2hs-btn--decline" @click="dismiss">
                        Not now
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

@media (display-mode: standalone) {
    .a2hs-backdrop { display: none !important; }
}
</style>
