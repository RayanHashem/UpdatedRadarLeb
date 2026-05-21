import '../css/app.css';
import 'bootstrap/dist/js/bootstrap.bundle.js';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import DesktopBlock from './components/DesktopBlock.vue';

/*
 * Global mobile-only gate.
 *
 * Dashboard.vue already mounts <DesktopBlock /> on the radar page, but
 * laptop users hitting /login, /register, /forgot-password (and the
 * reset-password link from email) were still able to interact with those
 * forms — which only makes sense as a prelude to playing the mobile-only
 * game. We hoist the same detection up to the Inertia root so the
 * warning overlay appears on every public page. The admin panel is a
 * separate Filament/Livewire app served from /admin and is not affected
 * by this module.
 *
 * Detection mirrors the helper in Dashboard.vue: known mobile UAs OR an
 * iPadOS 13+ device that reports as Macintosh but exposes multi-touch.
 */
function isDesktopEnv(): boolean {
    if (typeof window === 'undefined' || typeof navigator === 'undefined') {
        return false;
    }
    const ua = navigator.userAgent || '';
    if (/iPhone|iPad|iPod|Android|webOS|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(ua)) {
        return false;
    }
    if (/Macintosh/i.test(ua) && (navigator.maxTouchPoints || 0) > 1) {
        return false;
    }
    return true;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// -----------------------------------------------------------
// Service Worker Registration
// TEMPORARILY DISABLED - Unregister all Service Workers to fix CSS loading issues
// -----------------------------------------------------------
if ('serviceWorker' in navigator) {
    // Immediately unregister all Service Workers and clear caches
    navigator.serviceWorker.getRegistrations().then((registrations) => {
        if (registrations.length > 0) {
            console.log(`Unregistering ${registrations.length} Service Worker(s)...`);
            registrations.forEach((registration) => {
                registration.unregister().then((success) => {
                    if (success) {
                        console.log('Service Worker unregistered successfully');
                    }
                });
            });
        }
        
        // Clear all caches
        caches.keys().then((cacheNames) => {
            if (cacheNames.length > 0) {
                console.log(`Clearing ${cacheNames.length} cache(s)...`);
                cacheNames.forEach((cacheName) => {
                    caches.delete(cacheName).then((deleted) => {
                        if (deleted) {
                            console.log(`Cache "${cacheName}" deleted`);
                        }
                    });
                });
            }
        });
    });
    
    // Prevent any new Service Worker registration
    // Service Workers are completely disabled for now
}


createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue', { eager: false })),
    setup({ el, App, props, plugin }) {
        const desktop = isDesktopEnv();
        const app = createApp({
            // On desktop we render the Inertia <App> AND the DesktopBlock
            // overlay on top of it (z-index: 100000 in the component). The
            // overlay fully covers the page and is interaction-blocking, so
            // the underlying form doesn't need to be removed from the DOM.
            // Dashboard.vue still mounts its own copy as defense-in-depth;
            // the duplicate is invisible (identical fixed overlay) and
            // harmless.
            render: () =>
                h('div', { class: 'app-root' }, [
                    h(App, props),
                    desktop ? h(DesktopBlock) : null,
                ]),
        });
        
        // Configure Ziggy - use from window (set by @routes) or from Inertia props
        const ziggyConfig = (window as any).Ziggy || props.initialPage?.props?.ziggy;
        app.use(plugin);
        if (ziggyConfig) {
            app.use(ZiggyVue, ziggyConfig);
        } else {
            app.use(ZiggyVue);
        }
        
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Fallback if the server returns 419 HTML for an Inertia request (stale CSRF) instead of 409 + X-Inertia-Location
document.addEventListener(
    'inertia:invalid',
    (event: Event) => {
        const e = event as CustomEvent<{ response?: { status?: number } }>;
        if (e.detail?.response?.status === 419) {
            window.location.reload();
        }
    },
    { capture: true },
);