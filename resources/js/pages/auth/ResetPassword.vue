<script setup lang="ts">
/*
 * Reset Password page — the destination of the link emailed by Laravel's
 * password-reset flow. Previously this used the generic AuthSimpleLayout
 * (centered max-w-md, no branding) and looked completely off-brand: black
 * background, unstyled inputs, plain blue button. We now mirror the
 * ForgotPassword.vue treatment: the Lebanese-flag crest above a glassy
 * form card with the pill-shaped `custom-input` fields and cyan
 * `btn-custom` actions defined in app.css. No layout component is used
 * because the auth pages in this codebase render their own section
 * wrapper (see Login.vue, ForgotPassword.vue) — keeping that pattern
 * consistent.
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
    <section id="sign-in" class="w-100 page-forgot">
        <div class="d-flex align-items-center flex-column h-100 p-3-5">
            <!-- Flag crest, centered above the form (matches Login / ForgotPassword) -->
            <div class="d-flex gap-4 flex-column w-100 align-items-center">
                <img src="/assets/imgs/Flag_of_Lebanon.png" class="flag" alt="Flag of Lebanon" />
            </div>

            <div class="form-container d-flex flex-column gap-5">
                <form @submit.prevent="submit">
                    <h2 class="text-center mb-3 reset-password-title">Reset password</h2>
                    <p class="small text-center mb-4 px-2 forgot-password-helper">
                        Enter and confirm your new password below.
                    </p>

                    <!-- Email is locked to the recipient of the reset link. -->
                    <input
                        type="email"
                        class="form-control mb-3 custom-input"
                        placeholder="Email"
                        id="email"
                        autocomplete="email"
                        v-model="form.email"
                        readonly
                    />
                    <InputError :message="form.errors.email" variant="material" />

                    <input
                        type="password"
                        class="form-control mb-3 custom-input"
                        placeholder="New password"
                        id="password"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="new-password"
                        v-model="form.password"
                    />
                    <InputError :message="form.errors.password" variant="material" />

                    <input
                        type="password"
                        class="form-control mb-3 custom-input"
                        placeholder="Confirm new password"
                        id="password_confirmation"
                        required
                        :tabindex="2"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                    />
                    <InputError :message="form.errors.password_confirmation" variant="material" />

                    <div class="d-flex justify-content-center align-items-center gap-3 mt-4 w-100">
                        <button
                            class="btn btn-custom-1 btn-custom"
                            style="background-color: var(--rl-color-cyan);"
                            type="submit"
                            :tabindex="3"
                            :disabled="form.processing"
                        >
                            <span v-if="!form.processing">Reset password</span>
                            <LoaderCircle v-else class="animate-spin" />
                        </button>
                    </div>

                    <hr
                        style="height:2px;border-width:0;color:gray;background-color:white;opacity:1; box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.42), 0 6px 20px 0 rgba(0, 0, 0, 0.84); width: 80%; margin: 20px auto;"
                    />

                    <div class="d-flex justify-content-center mt-3 w-100">
                        <Link
                            href="/login"
                            class="btn btn-custom-1 btn-custom"
                            style="background-color: var(--rl-color-cyan); color: var(--rl-color-text);"
                            :tabindex="4"
                        >
                            Back to Login
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>

<style scoped>
.reset-password-title {
    color: #ffffff;
    font-weight: 700;
    letter-spacing: 0.01em;
}
</style>
