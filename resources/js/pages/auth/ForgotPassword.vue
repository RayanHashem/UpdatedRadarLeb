<script setup lang="ts">
/*
 * Forgot password — Sign in style (2026 redesign). Not part of the client
 * design, so it reuses the Sign in screen's rings, logo, fields and CTA.
 * English only, like before. Posts the email to /forgot-password; the
 * server answers with `status` (link sent) or an email error.
 */
import InputError from '@/components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head title="Forgot password" />
    <main class="rl-screen">
        <span class="rl-ring rl-hero__ring rl-hero__ring--outer" aria-hidden="true"></span>
        <span class="rl-ring rl-hero__ring rl-hero__ring--mid" aria-hidden="true"></span>
        <span class="rl-ring rl-hero__ring rl-hero__ring--inner" aria-hidden="true"></span>

        <div class="rl-screen__column">
            <img src="/assets/imgs/radarleb-logo.png" class="rl-hero__logo" width="176" height="176" alt="Radar Leb" />

            <div class="rl-hero__heading">
                <h1 class="rl-title rl-hero__title">Forgot password</h1>
                <p class="rl-hero__subtitle">Use the email address you signed up with (sign-in uses your phone number).</p>
            </div>

            <!-- Server confirmation, e.g. "A reset link has been sent to your email." -->
            <div v-if="status" class="rl-notice" role="status">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="forgot__notice-icon">
                    <rect x="3" y="5.5" width="18" height="13" rx="2.5" />
                    <path d="M3.5 7l8.5 6 8.5-6" />
                </svg>
                <span>{{ status }}</span>
            </div>

            <form class="rl-form forgot__form" @submit.prevent="submit">
                <div class="rl-field">
                    <label for="email" class="rl-label">Email</label>
                    <input
                        type="email"
                        class="rl-input"
                        placeholder="you@example.com"
                        id="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        :aria-invalid="form.errors.email ? 'true' : undefined"
                        aria-describedby="email-error"
                        v-model="form.email"
                    />
                    <InputError id="email-error" :message="form.errors.email" variant="inline" />
                </div>

                <button class="rl-button forgot__submit" type="submit" :tabindex="2" :disabled="form.processing">
                    <span v-if="!form.processing">Send Reset Link</span>
                    <span v-else>Sending...</span>
                </button>
            </form>

            <p class="rl-footer">
                <Link href="/login" class="rl-link rl-link--strong" :tabindex="3">Back to Login</Link>
            </p>
        </div>
    </main>
</template>

<style scoped>
.forgot__notice-icon {
    flex-shrink: 0;
    margin-top: 1px;
}

.forgot__form {
    margin-top: 24px;
}

.forgot__submit {
    margin-top: 8px;
}
</style>
