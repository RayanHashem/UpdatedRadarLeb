<script setup lang="ts">
/*
 * Desktop block for the main game (Dashboard).
 *
 * The RadarLeb game is intentionally mobile-only: the experience is built
 * around walking, location, and short tap interactions. Opening it on a
 * laptop misses the point and confuses new users who think the radar is
 * "broken" because nothing reacts to movement. We therefore present a
 * full-screen overlay that explains the constraint and points users to
 * their phone.
 *
 * Scope:
 *   - Mounted ONLY by Dashboard.vue.
 *   - The Filament admin panel (/admin) is a completely separate Livewire
 *     app — this component never reaches it, so admins keep desktop access.
 *   - Login / register / legal pages are not affected: users can still sign
 *     up from a laptop, they're just told to play on mobile after that.
 */
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="desktop-block" role="dialog" aria-modal="true" aria-labelledby="desktop-block-title">
        <div class="desktop-block__card">
            <img src="/assets/imgs/logo.png" alt="RadarLeb" class="desktop-block__logo" />
            <h1 id="desktop-block-title" class="desktop-block__title">Play on your phone</h1>
            <p class="desktop-block__body">
                RadarLeb is a mobile-only experience. The radar uses your phone's
                location and movement around Lebanon — that's where the fun is.
                Please open this page on your mobile device to play.
            </p>

            <div class="desktop-block__steps">
                <div class="desktop-block__step">
                    <span class="desktop-block__step-num">1</span>
                    <span>Open Safari or Chrome on your phone.</span>
                </div>
                <div class="desktop-block__step">
                    <span class="desktop-block__step-num">2</span>
                    <span>Type <strong>radarleb.com</strong> in the address bar.</span>
                </div>
                <div class="desktop-block__step">
                    <span class="desktop-block__step-num">3</span>
                    <span>Sign in with your account and start scanning.</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.desktop-block {
    position: fixed;
    inset: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background:
        radial-gradient(ellipse at top, rgba(98, 195, 255, 0.18), transparent 60%),
        linear-gradient(180deg, #06212e 0%, #03141d 100%);
    color: #ffffff;
}

.desktop-block__card {
    width: 100%;
    max-width: 30rem;
    padding: 2rem 1.75rem;
    background: rgba(6, 33, 46, 0.92);
    border: 1px solid rgba(98, 195, 255, 0.25);
    border-radius: 18px;
    box-shadow: 0 18px 50px rgba(0, 0, 0, 0.5);
    text-align: center;
}

.desktop-block__logo {
    width: 88px;
    height: auto;
    margin: 0 auto 0.85rem;
    display: block;
}

.desktop-block__title {
    margin: 0 0 0.65rem;
    font-size: 1.6rem;
    font-weight: 700;
    letter-spacing: 0.01em;
}

.desktop-block__body {
    margin: 0 0 1.4rem;
    font-size: 0.95rem;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.85);
}

.desktop-block__steps {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    margin: 0 auto 1.25rem;
    max-width: 22rem;
    text-align: start;
}

.desktop-block__step {
    display: grid;
    grid-template-columns: 28px minmax(0, 1fr);
    gap: 0.6rem;
    align-items: center;
    font-size: 0.9rem;
    line-height: 1.4;
    color: rgba(255, 255, 255, 0.9);
}

.desktop-block__step-num {
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

.desktop-block__note {
    margin: 0;
    font-size: 0.82rem;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.7);
}

.desktop-block__link {
    color: #62c3ff;
    text-decoration: underline;
    text-underline-offset: 2px;
}
</style>
