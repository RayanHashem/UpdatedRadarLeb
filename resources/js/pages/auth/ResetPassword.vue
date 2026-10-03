<script setup lang="ts">
/*
 * Reset Password page — the destination of the link emailed by Laravel's
 * password-reset flow. Sign in style (2026 redesign): radar rings, logo,
 * labelled fields and the red CTA, like Sign in / Forgot password.
 * English only, like before. The email is locked to the recipient of the
 * link; the form posts token + email + new password to password.store.
 */
import InputError from '@/components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Reset password" />
    <main class="rl-screen">
        <span class="rl-ring rl-hero__ring rl-hero__ring--outer" aria-hidden="true"></span>
        <span class="rl-ring rl-hero__ring rl-hero__ring--mid" aria-hidden="true"></span>
        <span class="rl-ring rl-hero__ring rl-hero__ring--inner" aria-hidden="true"></span>

        <div class="rl-screen__column">
            <img src="/assets/imgs/radarleb-logo.png" class="rl-hero__logo" width="176" height="176" alt="Radar Leb" />

            <div class="rl-hero__heading">
                <h1 class="rl-title rl-hero__title">Reset password</h1>
                <p class="rl-hero__subtitle">Enter and confirm your new password below.</p>
            </div>

            <form class="rl-form reset__form" @submit.prevent="submit">
                <!-- Email is locked to the recipient of the reset link. -->
                <div class="rl-field">
                    <label for="email" class="rl-label">Email</label>
                    <input
                        type="email"
                        class="rl-input reset__locked"
                        id="email"
                        autocomplete="email"
                        v-model="form.email"
                        readonly
                        :aria-invalid="form.errors.email ? 'true' : undefined"
                        aria-describedby="email-error"
                    />
                    <InputError id="email-error" :message="form.errors.email" variant="inline" />
                </div>

                <div class="rl-field">
                    <label for="password" class="rl-label">New password</label>
                    <input
                        type="password"
                        class="rl-input"
                        id="password"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="new-password"
                        :aria-invalid="form.errors.password ? 'true' : undefined"
                        aria-describedby="password-error"
                        v-model="form.password"
                    />
                    <InputError id="password-error" :message="form.errors.password" variant="inline" />
                </div>

                <div class="rl-field">
                    <label for="password_confirmation" class="rl-label">Confirm new password</label>
                    <input
                        type="password"
                        class="rl-input"
                        id="password_confirmation"
                        required
                        :tabindex="2"
                        autocomplete="new-password"
                        :aria-invalid="form.errors.password_confirmation ? 'true' : undefined"
                        aria-describedby="password_confirmation-error"
                        v-model="form.password_confirmation"
                    />
                    <InputError id="password_confirmation-error" :message="form.errors.password_confirmation" variant="inline" />
                </div>

                <button class="rl-button reset__submit" type="submit" :tabindex="3" :disabled="form.processing">
                    <span v-if="!form.processing">Reset password</span>
                    <LoaderCircle v-else class="animate-spin" />
                </button>
            </form>

            <p class="rl-footer">
                <Link href="/login" class="rl-link rl-link--strong" :tabindex="4">Back to Login</Link>
            </p>
        </div>
    </main>
</template>

<style scoped>
.reset__form {
    margin-top: 24px;
}

/* Read-only email: visibly locked. */
.reset__locked {
    background: var(--rl-color-night-sunken);
    color: var(--rl-color-bone-muted);
}

.reset__locked:focus {
    border-color: var(--rl-color-night-line);
}

.reset__submit {
    margin-top: 8px;
}
</style>
