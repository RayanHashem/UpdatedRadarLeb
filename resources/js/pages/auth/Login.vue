<script setup lang="ts">
/*
 * Login page — adapted from the Figma "Sign-in Page" (full-page 6 desktop /
 * full-page 7 mobile). Combines visual sign-up + sign-in entry in one view:
 *   - Sign in posts the form to /login (Mobile Number + Password only).
 *   - Sign up navigates to /register and prefills phone if entered so the user
 *     doesn't have to retype it.
 *
 * Layout: centered on mobile (Figma "Small Screen"), right-anchored on
 * desktop (Figma "Big Screen"). The .page-login background image stays
 * full-bleed; the form container is positioned via Bootstrap utilities.
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
    <section id="sign-in" class="w-100 page-login login-figma">
        <!--
          Layout responsive to Figma frames:
            • Mobile  (Small Screen): logo centred above the form, form centred.
            • Desktop (Big Screen)  : logo top-right, form pinned to the right
                                       half. Driven by the .login-figma rules
                                       in the <style> block at the bottom of
                                       this file (avoid Tailwind/Bootstrap
                                       class-name mixing).
        -->
        <div class="login-figma__layout d-flex align-items-center flex-column h-100 p-3-5">

            <!-- Logo block. Centred on mobile, parked top-right on desktop. -->
            <div class="login-figma__logo d-flex gap-4 flex-column align-items-center">
                <img src="/assets/imgs/Flag_of_Lebanon.png" class="flag" alt="Flag of Lebanon" />
            </div>

            <!--
              Form container — same .form-container CSS as before so the
              pill-shaped fields and dark-glass background still apply. On
              desktop we cap the width and pad it from the right edge via
              .login-figma__form-shell below.
            -->
            <div class="login-figma__form-shell form-container d-flex flex-column gap-5">
                <form @submit.prevent="submit">

                    <!-- Mobile Number (login credential 1 of 2). -->
                    <input
                        type="text"
                        class="form-control mb-3 custom-input"
                        placeholder="Mobile Number"
                        id="phone_number"
                        required
                        :tabindex="1"
                        autocomplete="tel"
                        inputmode="numeric"
                        v-model="form.phone_number"
                    />
                    <InputError :message="form.errors.phone_number" variant="material" />

                    <!-- Password (login credential 2 of 2). -->
                    <input
                        type="password"
                        class="form-control mb-3 custom-input"
                        placeholder="Password"
                        id="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                    />
                    <InputError :message="form.errors.password" variant="material" />

                    <!--
                      Buttons row: Sign up (coral) | Sign in (cyan). Colors
                      sourced from the Figma "Game Data" palette via
                      design tokens (resources/css/tokens.css).
                    -->
                    <div class="d-flex justify-content-center align-items-center gap-3 mt-4 w-100 auth-button-row">
                        <button
                            type="button"
                            class="btn btn-custom-2 btn-custom"
                            style="font-weight: normal; background-color: var(--rl-color-coral);"
                            :tabindex="3"
                            @click="goToSignUp"
                        >
                            Sign up
                        </button>
                        <button
                            class="btn btn-custom-1 btn-custom"
                            style="background-color: var(--rl-color-cyan);"
                            type="submit"
                            :tabindex="4"
                            :disabled="form.processing"
                        >
                            <span v-if="!form.processing">Sign in</span>
                            <LoaderCircle v-else class="animate-spin" />
                        </button>
                    </div>

                    <hr class="auth-divider">

                    <div class="d-flex justify-content-center mt-3 w-100">
                        <a
                            href="/forgot-password"
                            class="btn btn-custom-1 btn-custom forgot-password-btn"
                            style="background-color: var(--rl-color-cyan); color: var(--rl-color-text);"
                            :tabindex="5"
                        >
                            Forgot Password?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>

<style scoped>
/*
 * Responsive layout overrides for the Figma "Big Screen" variant.
 * 768px is the Tailwind / Bootstrap md breakpoint we standardise on
 * elsewhere in the app. On screens narrower than that the layout falls
 * through to the centered mobile rendering.
 *
 * Cap the form width even on mobile so very wide phones (or tablets in
 * portrait) don't stretch the pill inputs unnecessarily.
 */
.login-figma__form-shell {
    width: 100%;
    max-width: 28rem;
}

@media (min-width: 768px) {
    .login-figma__layout {
        flex-direction: row !important;          /* override .flex-column */
        justify-content: flex-end;
        align-items: center;
    }

    .login-figma__logo {
        position: absolute;
        top: 0;
        right: 0;
        width: auto;
        padding: 1.5rem;
    }

    .login-figma__form-shell {
        margin-right: clamp(2rem, 8vw, 6rem);
        margin-top: auto;
        margin-bottom: auto;
    }
}
</style>
