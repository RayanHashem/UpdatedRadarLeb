<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { RADARLEB_TERMS_FULL } from '@/content/radarlebTermsFull';

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

/*
 * Terms & Conditions consent modal.
 *
 * Tapping the checkbox while it's unchecked must NOT toggle it on directly —
 * we first force the user through a modal that scrolls the full Terms text
 * and offers explicit "I agree" / "I disagree" controls. Only "I agree"
 * applies the tick; "I disagree" leaves the box unchecked. Tapping a
 * box that's already checked just unchecks it (normal behaviour).
 *
 * We intercept the click via @click.prevent rather than @change so we get
 * a chance to block the underlying state change before Vue applies it.
 */
const showTermsModal = ref(false);

function onTermsCheckboxClick(event: Event) {
    if (form.confirm_18_and_terms) {
        // Already agreed — let the click through to uncheck.
        return;
    }
    event.preventDefault();
    showTermsModal.value = true;
}

function agreeToTerms() {
    form.confirm_18_and_terms = true;
    showTermsModal.value = false;
}

function disagreeToTerms() {
    form.confirm_18_and_terms = false;
    showTermsModal.value = false;
}

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
    <main class="rl-screen">
        <!-- Decorative radar rings behind the header. -->
        <span class="rl-ring signup__ring signup__ring--outer" aria-hidden="true"></span>
        <span class="rl-ring signup__ring signup__ring--inner" aria-hidden="true"></span>

        <div class="rl-screen__column">
            <!-- The design's EN/AR toggle (right of this bar) only lives in-game. -->
            <div class="signup__topbar">
                <Link :href="route('login')" class="signup__back" aria-label="Back to sign in">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 5l-7 7 7 7" />
                    </svg>
                </Link>
            </div>

            <div class="signup__header">
                <img src="/assets/imgs/radarleb-logo.png" class="signup__logo" width="104" height="104" alt="Radar Leb" />
                <h1 class="rl-title signup__title">Create your account</h1>
            </div>

            <form @submit.prevent="submit" class="rl-form signup__form">
                <div class="rl-field">
                    <label for="name" class="rl-label">Full name</label>
                    <input
                        v-model="form.name"
                        class="rl-input"
                        id="name"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        type="text"
                        :aria-invalid="form.errors.name ? 'true' : undefined"
                        aria-describedby="name-error"
                    />
                    <InputError id="name-error" :message="form.errors.name" variant="inline" />
                </div>

                <div class="rl-field">
                    <label for="email" class="rl-label">Email</label>
                    <input
                        v-model="form.email"
                        class="rl-input"
                        id="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        type="email"
                        :aria-invalid="form.errors.email ? 'true' : undefined"
                        aria-describedby="email-error"
                    />
                    <InputError id="email-error" :message="form.errors.email" variant="inline" />
                </div>

                <div class="signup__pair">
                    <div class="rl-field">
                        <label for="phone_number" class="rl-label">Phone number</label>
                        <!--
                          Digits-only placeholder: the backend only accepts
                          digits (RegisteredUserController), so the design's
                          "+961" hint would lead to a validation error.
                        -->
                        <input
                            v-model="form.phone_number"
                            class="rl-input"
                            placeholder="70123456"
                            id="phone_number"
                            required
                            :tabindex="3"
                            autocomplete="tel"
                            type="tel"
                            :aria-invalid="form.errors.phone_number ? 'true' : undefined"
                            aria-describedby="phone_number-error"
                        />
                        <InputError id="phone_number-error" :message="form.errors.phone_number" variant="inline" />
                    </div>

                    <!-- Date of Birth (DD/MM/YYYY) -->
                    <div class="rl-field">
                        <label for="date_of_birth" class="rl-label">Date of birth</label>
                        <input
                            :value="dobDisplay"
                            class="rl-input rl-input--mono"
                            placeholder="DD/MM/YYYY"
                            id="date_of_birth"
                            :tabindex="4"
                            autocomplete="bday"
                            type="text"
                            inputmode="numeric"
                            maxlength="10"
                            :aria-invalid="showDobError && dobErrorMessage ? 'true' : undefined"
                            aria-describedby="date_of_birth-error"
                            @input="formatDobInput"
                        />
                        <InputError
                            v-if="showDobError && dobErrorMessage"
                            id="date_of_birth-error"
                            :message="dobErrorMessage"
                            variant="inline"
                        />
                    </div>
                </div>

                <div class="rl-field">
                    <label for="password" class="rl-label">Password</label>
                    <input
                        v-model="form.password"
                        class="rl-input"
                        id="password"
                        required
                        :tabindex="5"
                        autocomplete="new-password"
                        type="password"
                        :aria-invalid="(form.password && !passwordAllValid) || form.errors.password ? 'true' : undefined"
                        aria-describedby="password-error"
                    />
                    <InputError
                        id="password-error"
                        :message="passwordSubmitError || form.errors.password"
                        variant="inline"
                    />
                </div>

                <div class="rl-field">
                    <label for="password_confirmation" class="rl-label">Confirm password</label>
                    <input
                        v-model="form.password_confirmation"
                        class="rl-input"
                        id="password_confirmation"
                        required
                        :tabindex="6"
                        autocomplete="new-password"
                        type="password"
                        :aria-invalid="
                            (form.password_confirmation && !passwordConfirmationValid) ||
                            (!passwordConfirmationValid && form.errors.password_confirmation)
                                ? 'true'
                                : undefined
                        "
                        aria-describedby="password_confirmation-error"
                    />
                    <InputError
                        v-if="form.password_confirmation && !passwordConfirmationValid"
                        id="password_confirmation-error"
                        message="Passwords do not match."
                        variant="inline"
                    />
                    <InputError
                        v-else-if="!passwordConfirmationValid && form.errors.password_confirmation"
                        id="password_confirmation-error"
                        :message="form.errors.password_confirmation"
                        variant="inline"
                    />
                </div>

                <!-- 18+ and Terms checkbox -->
                <!-- The whole card is the checkbox label: a tap anywhere on it ticks the box (44px+ target). -->
                <label class="signup__terms" for="confirm_18_and_terms">
                    <input
                        v-model="form.confirm_18_and_terms"
                        type="checkbox"
                        id="confirm_18_and_terms"
                        class="signup__terms-check"
                        :tabindex="7"
                        @click="onTermsCheckboxClick"
                    />
                    <span class="signup__terms-label">
                        I confirm I'm at least 18 years old and I agree to&nbsp;<span class="terms-consent-tail"
                            >the <Link
                                :href="route('terms')"
                                class="rl-link rl-link--strong"
                                @click.stop
                                >Terms &amp; Privacy Policy</Link
                            >.</span
                        >
                    </span>
                </label>
                <InputError :message="form.errors.confirm_18_and_terms" variant="inline" />

                <button class="rl-button signup__submit" type="submit" :disabled="!canSubmit" :tabindex="8">
                    Sign up
                </button>
            </form>

            <p class="rl-footer">
                <span class="rl-footer__hint">Already have an account?</span>
                <Link as="button" :href="route('login')" class="rl-link rl-link--strong" :tabindex="9">
                    Sign in
                </Link>
            </p>
        </div>

        <!--
          Terms & Conditions consent modal — appears when the user taps the
          "I confirm I'm 18+" checkbox while it's unchecked. The user must
          read (or scroll past) the terms and explicitly press "I agree" to
          apply the tick. Pressing "I disagree" leaves the box unchecked.
        -->
        <div
            v-if="showTermsModal"
            class="terms-modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-labelledby="terms-modal-title"
        >
            <div class="terms-modal-card">
                <h2 id="terms-modal-title" class="rl-title terms-modal-title">Terms &amp; Conditions</h2>
                <div class="terms-modal-scroll">
                    <pre class="terms-modal-text">{{ RADARLEB_TERMS_FULL }}</pre>
                </div>
                <div class="terms-modal-actions">
                    <button
                        type="button"
                        class="rl-button rl-button--secondary terms-modal-btn"
                        @click="disagreeToTerms"
                    >
                        I disagree
                    </button>
                    <button
                        type="button"
                        class="rl-button terms-modal-btn"
                        @click="agreeToTerms"
                    >
                        I agree
                    </button>
                </div>
            </div>
        </div>
    </main>
</template>

<style scoped>
/*
 * Shared field / button / footer styles live in resources/css/redesign.css;
 * only this screen's layout is here.
 */

/* Ring centre sits 110px from the top of the design frame. */
.signup__ring {
    top: calc(110px + env(safe-area-inset-top, 0px));
}

.signup__ring--outer { width: 360px; height: 360px; border-color: var(--rl-color-ring-outer); }
.signup__ring--inner { width: 240px; height: 240px; border-color: var(--rl-color-ring-inner); }

.signup__topbar {
    display: flex;
    align-items: center;
}

.signup__back {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    width: 44px;
    height: 44px;
    color: var(--rl-color-bone);
}

.signup__back:hover {
    color: #ffffff;
}

.signup__back:focus-visible {
    outline: 2px solid var(--rl-color-bone);
    outline-offset: 2px;
}

.signup__header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-top: 8px;
}

.signup__logo {
    display: block;
    flex-shrink: 0;
    width: 104px;
    height: 104px;
}

.signup__title {
    font-size: 36px;
    line-height: 0.95;
}

.signup__form {
    margin-top: 24px;
}

.signup__pair {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.signup__terms {
    display: flex;
    cursor: pointer;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: var(--rl-radius-field);
    background: var(--rl-color-night-sunken);
}

.signup__terms-check {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    margin: 1px 0 0;
    accent-color: var(--rl-color-signal);
    cursor: pointer;
}

.signup__terms-label {
    flex-grow: 1;
    font-size: 14px;
    line-height: 1.45;
    color: var(--rl-color-bone-soft);
    cursor: pointer;
}

/* Keep “the” + link on one line so it doesn’t stack as “the” / “Terms…” */
.terms-consent-tail {
    white-space: nowrap;
}

.signup__submit {
    margin-top: 4px;
}

/*
 * Terms & Conditions consent modal. Sits above the form and locks the
 * page until the user picks I agree / I disagree. The design has no terms
 * dialog, so this borrows the popup card from the "Antenna detected" screen.
 */
.terms-modal-backdrop {
    position: fixed;
    inset: 0;
    background: var(--rl-color-dialog-backdrop);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    z-index: 1050;
}

.terms-modal-card {
    width: 100%;
    max-width: 32rem;
    max-height: min(90svh, 40rem);
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 28px 22px 22px;
    border: 1px solid var(--rl-color-dialog-edge);
    border-radius: 22px;
    background: var(--rl-color-night-raised);
    color: var(--rl-color-bone);
    font-family: var(--rl-font-sans);
}

.terms-modal-title {
    text-align: center;
    font-size: 32px;
}

.terms-modal-scroll {
    flex: 1 1 auto;
    min-height: 8rem;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    padding: 12px 14px;
    border: 1px solid var(--rl-color-night-edge);
    border-radius: var(--rl-radius-field);
    background: var(--rl-color-night-sunken);
}

.terms-modal-text {
    margin: 0;
    padding: 0;
    font-family: inherit;
    font-size: 13px;
    line-height: 1.5;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    color: var(--rl-color-bone-soft);
}

.terms-modal-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

/* Side by side, both buttons use the secondary (52px) size. */
.terms-modal-btn {
    height: 52px;
    font-size: 20px;
}
</style>
