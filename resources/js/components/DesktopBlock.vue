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
 *
 * Look: Sign in style (2026 redesign) — rings, logo and heading from the
 * Sign in screen, steps numbered like How to play. English only, so the
 * overlay pins lang/dir even when the game behind it is in Arabic.
 */
</script>

<template>
    <div class="desktop-block" role="dialog" aria-modal="true" aria-labelledby="desktop-block-title" lang="en" dir="ltr">
        <div class="desktop-block__column">
            <div class="desktop-block__hero">
                <span class="rl-ring rl-hero__ring--outer desktop-block__ring" aria-hidden="true"></span>
                <span class="rl-ring rl-hero__ring--mid desktop-block__ring" aria-hidden="true"></span>
                <span class="rl-ring rl-hero__ring--inner desktop-block__ring" aria-hidden="true"></span>
                <img src="/assets/imgs/radarleb-logo.png" alt="RadarLeb" class="desktop-block__logo" width="176" height="176" />
            </div>

            <div class="rl-hero__heading desktop-block__heading">
                <h1 id="desktop-block-title" class="rl-title rl-hero__title">Play on your phone</h1>
                <p class="rl-hero__subtitle desktop-block__body">
                    RadarLeb is a mobile-only experience. Please open this page on your
                    mobile device to play.
                </p>
            </div>

            <div class="desktop-block__steps">
                <div class="desktop-block__step">
                    <span class="desktop-block__step-num">01</span>
                    <span>Open Safari or Chrome on your phone.</span>
                </div>
                <div class="desktop-block__step">
                    <span class="desktop-block__step-num">02</span>
                    <span>Type <strong>radarleb.com</strong> in the address bar.</span>
                </div>
                <div class="desktop-block__step">
                    <span class="desktop-block__step-num">03</span>
                    <span>Sign in with your account and start scanning.</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Covers the whole window; scrolls if the window is shorter than the content. */
.desktop-block {
    position: fixed;
    inset: 0;
    z-index: 100000;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
    overflow-y: auto;
    background: var(--rl-color-night);
    color: var(--rl-color-bone);
    font-family: var(--rl-font-sans);
}

/*
 * margin:auto centres the column vertically without clipping it in short
 * windows. isolation keeps the rings (z-index -1) behind the text but above
 * the page background.
 */
.desktop-block__column {
    isolation: isolate;
    box-sizing: border-box;
    width: 100%;
    max-width: 480px;
    margin: auto;
    padding: 48px 24px;
}

/* Rings centred on the logo, like Sign in. */
.desktop-block__hero {
    position: relative;
    display: flex;
    justify-content: center;
}

.desktop-block__ring {
    top: 50%;
    z-index: -1;
}

.desktop-block__logo {
    position: relative;
    display: block;
    width: 176px;
    height: 176px;
}

/* Heading and text centred under the logo; the steps card stays left-aligned. */
.desktop-block__heading {
    text-align: center;
}

.desktop-block__body {
    margin-top: 4px;
}

.desktop-block__steps {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 24px;
    padding: 16px;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: 16px;
    background: var(--rl-color-night-raised);
}

.desktop-block__step {
    display: grid;
    grid-template-columns: 28px minmax(0, 1fr);
    gap: 10px;
    font-size: 15px;
    line-height: 1.55;
    color: var(--rl-color-bone-soft);
}

.desktop-block__step strong {
    font-weight: 600;
    color: var(--rl-color-bone);
}

.desktop-block__step-num {
    padding-top: 2px;
    font-family: var(--rl-font-mono);
    font-size: 13px;
    color: var(--rl-color-alert);
}
</style>
