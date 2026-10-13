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
    <main class="rl-screen">
        <!-- Decorative radar rings behind the logo. -->
        <span class="rl-ring signin__ring signin__ring--outer" aria-hidden="true"></span>
        <span class="rl-ring signin__ring signin__ring--mid" aria-hidden="true"></span>
        <span class="rl-ring signin__ring signin__ring--inner" aria-hidden="true"></span>

        <div class="rl-screen__column">
            <img src="/assets/imgs/radarleb-logo.png" class="signin__logo" width="176" height="176" alt="Radar Leb" />

            <div class="signin__heading">
                <h1 class="rl-title signin__title">Welcome back</h1>
                <p class="signin__subtitle">Sign in to keep scanning.</p>
            </div>

            <!-- Server status after a password reset ("Your password has been reset."). -->
            <div v-if="status" class="rl-notice" role="status">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="signin__notice-icon">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M8 12.5l2.7 2.7L16 9.8" />
                </svg>
                <span>{{ status }}</span>
            </div>

            <form class="rl-form signin__form" @submit.prevent="submit">
                <div class="rl-field">
                    <label for="phone_number" class="rl-label">Mobile number</label>
                    <!--
                      Placeholder is digits-only on purpose: registration only
                      accepts digits (see RegisteredUserController), so the
                      design's "+961 70 123 456" hint would lead people to
                      type a value that never matches.
                    -->
                    <input
                        type="text"
                        class="rl-input"
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

                <div class="rl-field">
                    <label for="password" class="rl-label">Password</label>
                    <input
                        type="password"
                        class="rl-input"
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
                    <a href="/forgot-password" class="rl-link signin__forgot" :tabindex="4">Forget password?</a>
                </div>

                <button class="rl-button signin__submit" type="submit" :tabindex="3" :disabled="form.processing">
                    <span v-if="!form.processing">Sign in</span>
                    <LoaderCircle v-else class="animate-spin" />
                </button>
            </form>

            <p class="rl-footer">
                <span class="rl-footer__hint">New here?</span>
                <button type="button" class="rl-link rl-link--strong" :tabindex="5" @click="goToSignUp">
                    Create an account
                </button>
            </p>
        </div>
    </main>
</template>

<style scoped>
/*
 * Shared field / button / footer styles live in resources/css/redesign.css;
 * only this screen's layout is here.
 */

/* Ring centre sits 190px from the top of the 390×844 frame, 30px below the logo's centre. */
.signin__ring {
    top: calc(190px + env(safe-area-inset-top, 0px));
}

.signin__ring--outer { width: 520px; height: 520px; border-color: var(--rl-color-ring-outer); }
.signin__ring--mid   { width: 380px; height: 380px; border-color: var(--rl-color-ring-mid); }
.signin__ring--inner { width: 250px; height: 250px; border-color: var(--rl-color-ring-inner); }

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
    font-size: 40px;
    letter-spacing: 0.02em;
}

.signin__subtitle {
    margin: 0;
    font-size: 15px;
    color: var(--rl-color-bone-muted);
}

.signin__notice-icon {
    flex-shrink: 0;
    margin-top: 1px;
}

.signin__form {
    margin-top: 24px;
}

/* Under the password field, right-aligned; 44px tall so it's easy to tap. */
.signin__forgot {
    display: inline-flex;
    align-self: flex-end;
    align-items: center;
    min-height: 44px;
    margin: -6px 0 -12px;
    font-size: 13px;
}

.signin__submit {
    margin-top: 8px;
}
</style>
