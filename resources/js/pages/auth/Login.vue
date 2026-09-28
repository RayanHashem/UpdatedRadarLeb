<script setup lang="ts">
/*
 * Login page — 2026 redesign "Sign in" screen (docs/design/, not in git).
 *   - Sign in posts the form to /login (Mobile Number + Password only).
 *   - "Create an account" navigates to /register and prefills phone if
 *     entered so the user doesn't have to retype it.
 *
 * Single mobile column centred on the page; desktop visitors get the
 * global DesktopBlock overlay from app.ts, so there is no desktop layout.
 */
import InputError from '@/components/InputError.vue';
import { useForm, router } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    phone_number: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

/**
 * Sign Up: take the user to /register and pass through whatever they've
 * already typed (phone) via query params. Register.vue reads these on mount.
 */
const goToSignUp = () => {
    router.visit(route('register'), {
        method: 'get',
        data: {
            phone_number: form.phone_number,
        },
    });
};
</script>

<template>
    <main class="signin">
        <!-- Decorative radar rings behind the logo. -->
        <div class="signin__rings" aria-hidden="true">
            <span class="signin__ring signin__ring--outer"></span>
            <span class="signin__ring signin__ring--mid"></span>
            <span class="signin__ring signin__ring--inner"></span>
        </div>

        <div class="signin__column">
            <img src="/assets/imgs/radarleb-logo.png" class="signin__logo" width="176" height="176" alt="Radar Leb" />

            <div class="signin__heading">
                <h1 class="signin__title">Welcome back</h1>
                <p class="signin__subtitle">Sign in to keep scanning.</p>
            </div>

            <form class="signin__form" @submit.prevent="submit">
                <div class="signin__field">
                    <label for="phone_number" class="signin__label">Mobile number</label>
                    <!--
                      Placeholder is digits-only on purpose: registration only
                      accepts digits (see RegisteredUserController), so the
                      design's "+961 70 123 456" hint would lead people to
                      type a value that never matches.
                    -->
                    <input
                        type="text"
                        class="signin__input"
                        placeholder="70123456"
                        id="phone_number"
                        required
                        :tabindex="1"
                        autocomplete="tel"
                        inputmode="numeric"
                        :aria-invalid="form.errors.phone_number ? 'true' : undefined"
                        aria-describedby="phone_number-error"
                        v-model="form.phone_number"
                    />
                    <InputError id="phone_number-error" :message="form.errors.phone_number" variant="inline" />
                </div>

                <div class="signin__field">
                    <div class="signin__label-row">
                        <label for="password" class="signin__label">Password</label>
                        <a href="/forgot-password" class="signin__link signin__link--small" :tabindex="4">Forgot password?</a>
                    </div>
                    <input
                        type="password"
                        class="signin__input"
                        placeholder="••••••••"
                        id="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        :aria-invalid="form.errors.password ? 'true' : undefined"
                        aria-describedby="password-error"
                        v-model="form.password"
                    />
                    <InputError id="password-error" :message="form.errors.password" variant="inline" />
                </div>

                <button class="signin__submit" type="submit" :tabindex="3" :disabled="form.processing">
                    <span v-if="!form.processing">Sign in</span>
                    <LoaderCircle v-else class="animate-spin" />
                </button>
            </form>

            <p class="signin__footer">
                <span class="signin__footer-hint">New here?</span>
                <button type="button" class="signin__link signin__link--strong" :tabindex="5" @click="goToSignUp">
                    Create an account
                </button>
            </p>
        </div>
    </main>
</template>

<style scoped>
/*
 * Owns its own scrolling: app.css pins body/html to overflow:hidden, so
 * short phones (iPhone SE) scroll inside .signin instead of the page.
 */
.signin {
    position: relative;
    height: 100svh;
    overflow-x: hidden;
    overflow-y: auto;
    background: var(--rl-color-night);
    color: var(--rl-color-bone);
    font-family: var(--rl-font-sans);
}

/* Ring centre sits 190px from the top of the 390×844 frame, 30px below the logo's centre. */
.signin__rings {
    position: absolute;
    top: calc(190px + env(safe-area-inset-top, 0px));
    left: 50%;
    width: 0;
    height: 0;
    pointer-events: none;
}

.signin__ring {
    position: absolute;
    border-radius: 50%;
    border: 1px solid;
    transform: translate(-50%, -50%);
}

.signin__ring--outer { width: 520px; height: 520px; border-color: var(--rl-color-ring-outer); }
.signin__ring--mid   { width: 380px; height: 380px; border-color: var(--rl-color-ring-mid); }
.signin__ring--inner { width: 250px; height: 250px; border-color: var(--rl-color-ring-inner); }

.signin__column {
    position: relative;
    display: flex;
    flex-direction: column;
    min-height: 100%;
    max-width: 480px;
    margin: 0 auto;
    padding: calc(16px + env(safe-area-inset-top, 0px)) 24px calc(28px + env(safe-area-inset-bottom, 0px));
}

/*
 * 56px = the design's 32px language toggle row + 24px gap above the logo.
 * The toggle only lives in-game; the space is kept so the layout matches.
 */
.signin__logo {
    display: block;
    width: 176px;
    height: 176px;
    margin: 56px auto 0;
}

.signin__heading {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 36px;
}

.signin__title {
    margin: 0;
    font-family: var(--rl-font-headline);
    font-weight: 800;
    font-size: 40px;
    line-height: 1;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: var(--rl-color-bone);
}

.signin__subtitle {
    margin: 0;
    font-size: 15px;
    color: var(--rl-color-bone-muted);
}

.signin__form {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-top: 24px;
}

.signin__field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.signin__label-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
}

.signin__label {
    font-family: var(--rl-font-mono);
    font-weight: 500;
    font-size: 11px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--rl-color-bone-muted);
}

.signin__input {
    height: 50px;
    box-sizing: border-box;
    padding: 0 14px;
    border: 1px solid var(--rl-color-night-line);
    border-radius: var(--rl-radius-field);
    background: var(--rl-color-night-raised);
    color: var(--rl-color-bone);
    font-family: var(--rl-font-sans);
    font-size: 16px;
}

.signin__input::placeholder {
    color: var(--rl-color-bone-faint);
}

.signin__input:focus {
    outline: none;
    border-color: var(--rl-color-bone);
}

.signin__input[aria-invalid='true'] {
    border: 1.5px solid var(--rl-color-alert);
}

.signin__submit {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 56px;
    margin-top: 8px;
    border: 0;
    border-radius: var(--rl-radius-button);
    background: var(--rl-color-signal);
    color: #ffffff;
    font-family: var(--rl-font-headline);
    font-weight: 800;
    font-size: 22px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    cursor: pointer;
}

.signin__submit:disabled {
    opacity: 0.7;
    cursor: default;
}

.signin__link {
    padding: 0;
    border: 0;
    background: none;
    color: var(--rl-color-bone);
    font: inherit;
    text-decoration: underline;
    cursor: pointer;
}

.signin__link:hover {
    color: #ffffff;
}

.signin__link--small {
    font-size: 13px;
}

.signin__link--strong {
    font-weight: 600;
}

.signin__submit:focus-visible,
.signin__link:focus-visible {
    outline: 2px solid var(--rl-color-bone);
    outline-offset: 2px;
}

/* margin-top:auto pins the footer to the bottom; padding keeps a gap on short screens. */
.signin__footer {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin: auto 0 0;
    padding-top: 24px;
    font-size: 15px;
}

.signin__footer-hint {
    color: var(--rl-color-bone-muted);
}
</style>
