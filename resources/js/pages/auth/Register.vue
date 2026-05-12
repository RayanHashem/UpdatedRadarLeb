<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    date_of_birth: '',
    password: '',
    password_confirmation: '',
    confirm_18_and_terms: false,
});

/*
 * Pre-fill from /login → "Sign up" hand-off.
 *
 * Login.vue's goToSignUp() does `router.visit(route('register'), { method: 'get', data: { name, phone_number } })`
 * which Inertia sends as ?name=...&phone_number=... query params. Hydrate the
 * form from those on mount so a user who started typing on /login doesn't have
 * to retype on /register.
 *
 * We do this client-side rather than via server-side props so this works even
 * when the controller doesn't pass them through (no backend coupling).
 */
onMounted(() => {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams(window.location.search);
    const prefilledName = params.get('name');
    const prefilledPhone = params.get('phone_number');
    if (prefilledName) form.name = prefilledName;
    if (prefilledPhone) form.phone_number = prefilledPhone;
});

const dobDisplay = ref('');
const submitAttempted = ref(false);

/** Password rules: must match backend validation exactly */
const PASSWORD_RULES = [
    { id: 'min', label: 'At least 8 characters', test: (p: string) => p.length >= 8 },
    { id: 'upper', label: 'At least one uppercase letter', test: (p: string) => /[A-Z]/.test(p) },
    { id: 'lower', label: 'At least one lowercase letter', test: (p: string) => /[a-z]/.test(p) },
    { id: 'number', label: 'At least one number', test: (p: string) => /\d/.test(p) },
    { id: 'special', label: 'At least one special character (!@#$%^&*)', test: (p: string) => /[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(p) },
] as const;

const passwordAllValid = computed(() =>
    PASSWORD_RULES.every((rule) => rule.test(form.password))
);

const passwordConfirmationValid = computed(() => {
    const p = String(form.password ?? '').trim();
    const pc = String(form.password_confirmation ?? '').trim();
    return p.length > 0 && pc === p;
});

const passwordSubmitError = computed(() => {
    if (!form.password) return null;
    if (!passwordAllValid.value) {
        const failed = PASSWORD_RULES.filter((r) => !r.test(form.password));
        return `Password must meet all requirements: ${failed.map((r) => r.label.toLowerCase()).join(', ')}`;
    }
    return null;
});

function parseDob(value: string): { day: number; month: number; year: number } | null {
    const re = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/;
    const m = value.trim().match(re);
    if (!m) return null;
    const day = parseInt(m[1], 10);
    const month = parseInt(m[2], 10);
    const year = parseInt(m[3], 10);
    if (month < 1 || month > 12 || day < 1 || day > 31 || year < 1900 || year > 2100) return null;
    const d = new Date(year, month - 1, day);
    if (d.getFullYear() !== year || d.getMonth() !== month - 1 || d.getDate() !== day) return null;
    return { day, month, year };
}

function ageFromDob(parsed: { day: number; month: number; year: number }): number {
    const today = new Date();
    let age = today.getFullYear() - parsed.year;
    const m = today.getMonth() - (parsed.month - 1);
    if (m < 0 || (m === 0 && today.getDate() < parsed.day)) age--;
    return age;
}

const dobError = computed(() => {
    const raw = form.date_of_birth.trim();
    if (!raw) return 'Date of birth is required';
    const parsed = parseDob(raw);
    if (!parsed) return 'Please enter a valid date (DD/MM/YYYY)';
    if (ageFromDob(parsed) < 18) return 'You must be at least 18 years old';
    return null;
});

/** Only show date error after Sign up attempt or when server returns an error (not on blur) */
const showDobError = computed(
    () => (submitAttempted.value && dobError.value) || !!form.errors.date_of_birth
);
const dobErrorMessage = computed(() => dobError.value || form.errors.date_of_birth || null);

const isDobValid = computed(() => !dobError.value);
const canSubmit = computed(
    () =>
        isDobValid.value &&
        form.confirm_18_and_terms &&
        passwordAllValid.value &&
        passwordConfirmationValid.value &&
        !form.processing
);

function formatDobInput(e: Event) {
    const input = e.target as HTMLInputElement;
    let v = input.value.replace(/\D/g, '');
    if (v.length > 8) v = v.slice(0, 8);
    const parts: string[] = [];
    if (v.length > 0) parts.push(v.slice(0, 2));
    if (v.length > 2) parts.push(v.slice(2, 4));
    if (v.length > 4) parts.push(v.slice(4, 8));
    const formatted = parts.join('/');
    dobDisplay.value = formatted;
    form.date_of_birth = formatted;
}

watch(
    () => form.date_of_birth,
    (val) => { dobDisplay.value = val; },
    { immediate: true }
);

const submit = () => {
    submitAttempted.value = true;
    if (!canSubmit.value) return;
    // Trim password fields so accidental spaces don't cause "passwords do not match"
    form.password = form.password.trim();
    form.password_confirmation = form.password_confirmation.trim();
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <section id="sign-in" class="w-100 page-register">
        <div class="d-flex align-items-center flex-column h-100 p-3-5">
            <div class="d-flex gap-2 flex-column w-100 align-items-center">
                <img src="/assets/imgs/Flag_of_Lebanon.png" class="flag" alt="Flag of Lebanon" />
            </div>
            <div class="form-container d-flex flex-column">
                <form @submit.prevent="submit" class="register-form-fields">
                    <input
                        v-model="form.name"
                        class="form-control custom-input"
                        placeholder="Full Name"
                        id="name"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        type="text"
                    />
                    <InputError :message="form.errors.name" variant="material" />

                    <input
                        v-model="form.email"
                        class="form-control custom-input"
                        placeholder="Email"
                        id="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        type="email"
                    />
                    <InputError :message="form.errors.email" variant="material" />

                    <input
                        v-model="form.phone_number"
                        class="form-control custom-input"
                        placeholder="Phone Number"
                        id="phone_number"
                        required
                        :tabindex="3"
                        autocomplete="tel"
                        type="tel"
                    />
                    <InputError :message="form.errors.phone_number" variant="material" />

                    <!-- Date of Birth (DD/MM/YYYY) -->
                    <input
                        :value="dobDisplay"
                        class="form-control custom-input"
                        placeholder="DD/MM/YYYY"
                        id="date_of_birth"
                        :tabindex="4"
                        autocomplete="bday"
                        type="text"
                        inputmode="numeric"
                        maxlength="10"
                        @input="formatDobInput"
                    />
                    <InputError
                        v-if="showDobError && dobErrorMessage"
                        :message="dobErrorMessage"
                        variant="material"
                    />

                    <input
                        v-model="form.password"
                        class="form-control custom-input"
                        placeholder="Password"
                        id="password"
                        required
                        :tabindex="5"
                        autocomplete="new-password"
                        type="password"
                        :class="{ 'is-invalid': form.password && !passwordAllValid }"
                    />
                    <InputError
                        :message="passwordSubmitError || form.errors.password"
                        variant="material"
                    />

                    <input
                        v-model="form.password_confirmation"
                        class="form-control custom-input"
                        placeholder="Confirm Password"
                        id="password_confirmation"
                        required
                        :tabindex="6"
                        autocomplete="new-password"
                        type="password"
                        :class="{ 'is-invalid': form.password_confirmation && !passwordConfirmationValid }"
                    />
                    <InputError
                        v-if="form.password_confirmation && !passwordConfirmationValid"
                        message="Passwords do not match."
                        variant="material"
                    />
                    <InputError
                        v-else-if="!passwordConfirmationValid && form.errors.password_confirmation"
                        :message="form.errors.password_confirmation"
                        variant="material"
                    />

                    <!-- 18+ and Terms checkbox -->
                    <div class="d-flex align-items-start gap-2 terms-checkbox-wrap">
                        <input
                            v-model="form.confirm_18_and_terms"
                            type="checkbox"
                            id="confirm_18_and_terms"
                            class="mt-1 form-check-input flex-shrink-0"
                            :tabindex="7"
                        />
                        <label for="confirm_18_and_terms" class="flex-grow-1 form-check-label text-white small mb-0 terms-consent-label">
                            I confirm I'm at least 18 years old and I agree to&nbsp;<span class="terms-consent-tail"
                                >the <Link
                                    :href="route('terms')"
                                    class="text-decoration-underline text-white terms-privacy-unified-link"
                                    @click.stop
                                    >Terms &amp; Privacy Policy</Link
                                >.</span
                            >
                        </label>
                    </div>
                    <InputError :message="form.errors.confirm_18_and_terms" variant="material" />

                    <div class="d-flex justify-content-center align-items-center gap-2 mt-2 w-100 auth-button-row">
                        <button
                            class="btn btn-custom-1 btn-custom"
                            style="font-weight: normal; background-color: var(--rl-color-coral);"
                            type="submit"
                            :disabled="!canSubmit"
                            :tabindex="8"
                        >
                            Sign up
                        </button>
                        <Link
                            as="button"
                            :href="route('login')"
                            class="btn btn-custom-2 btn-custom"
                            :tabindex="9"
                            style="background-color: var(--rl-color-cyan);"
                        >
                            Sign in
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Keep “the” + link on one line so it doesn’t stack as “the” / “Terms…” */
.terms-consent-tail {
    white-space: nowrap;
}
</style>
