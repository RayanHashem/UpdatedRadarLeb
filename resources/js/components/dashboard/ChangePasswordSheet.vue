<script setup lang="ts">
/*
 * Change password page (2026 redesign "Settings — change password" screen).
 *
 * Presentation only. Dashboard.vue still owns the two-step flow and the
 * rules: step 1 verifies the old password (verifyOldPassword), step 2 sets
 * the new one (updatePassword, Sign up's five password rules + matching confirmation),
 * Back returns to Settings (resetPasswordFlow).
 *
 * Error text arrives either from the app (already translated with t()) or
 * straight from the server in English. In Arabic, known server messages are
 * shown in Arabic here; anything unknown falls back to a generic Arabic error.
 */
import { Link } from '@inertiajs/vue3'
import InputError from '@/components/InputError.vue'
import RadarIcon from '@/components/dashboard/RadarIcon.vue'
import { useTranslate } from '@/composables/useTranslate'

const props = defineProps<{
    verified: boolean
    wrongAttempts: number
    verifying: boolean
    updating: boolean
    success: boolean
    passwordError: string
    newPasswordError: string
    confirmPasswordError: string
}>()

const oldPassword = defineModel<string>('oldPassword', { required: true })
const newPassword = defineModel<string>('newPassword', { required: true })
const confirmPassword = defineModel<string>('confirmPassword', { required: true })

const emit = defineEmits<{
    (e: 'verify'): void
    (e: 'update'): void
    (e: 'back'): void
}>()

const { t, locale } = useTranslate()

/* English messages the password endpoints can return (SettingsController + Laravel defaults). */
const SERVER_MESSAGE_KEYS: Record<string, string> = {
    'Incorrect password': 'dashboard.password.server.incorrect',
    'The provided password is incorrect.': 'dashboard.password.server.providedIncorrect',
    'Too Many Attempts.': 'dashboard.password.server.tooManyAttempts',
    'CSRF token mismatch.': 'dashboard.password.server.sessionExpired',
    'The old password field is required.': 'dashboard.password.error.enterOld',
    'The new password field is required.': 'dashboard.password.error.enterNew',
    'The new password field must be at least 8 characters.': 'dashboard.password.error.minLength',
    'The new password field must contain at least one letter.': 'dashboard.password.server.letters',
    'The new password field must contain at least one uppercase and one lowercase letter.': 'dashboard.password.server.mixedCase',
    'The new password field must contain at least one number.': 'dashboard.password.server.numbers',
    'The new password field must contain at least one symbol.': 'dashboard.password.server.symbols',
    'The new password field confirmation does not match.': 'dashboard.password.error.mismatch',
    'Server Error': 'dashboard.password.error.generic',
}

function localize(message: string): string {
    if (!message || locale.value !== 'ar') return message
    const key = SERVER_MESSAGE_KEYS[message]
    if (key) return t(key)
    // Already Arabic (came through t()) → keep; unknown English server text → generic Arabic error.
    return /[؀-ۿ]/.test(message) ? message : t('dashboard.password.error.generic')
}
</script>

<template>
    <div class="rl-sheet" role="dialog" aria-modal="true" aria-labelledby="password-title">
        <div class="rl-sheet__column">
            <div class="rl-sheet__bar">
                <button type="button" class="rl-sheet__close password__back-icon" :aria-label="t('dashboard.password.backToSettings')" @click="emit('back')">
                    <RadarIcon name="chevron" :size="20" :stroke="1.8" />
                </button>
                <span class="rl-sheet__brand">{{ t(props.verified ? 'dashboard.password.step2' : 'dashboard.password.step1') }}</span>
            </div>

            <!-- Shown for 2 s after a successful change, before Dashboard returns to Settings. -->
            <div v-if="success" class="password__success" role="status">
                <RadarIcon name="lock" :size="20" />
                <span>{{ t('dashboard.password.success') }}</span>
            </div>

            <!-- Step 1: verify the old password -->
            <template v-if="!verified">
                <h1 id="password-title" class="rl-title password__title">{{ t('dashboard.password.title1') }}</h1>
                <p class="password__subtitle">{{ t('dashboard.password.subtitle1') }}</p>

                <div class="rl-field">
                    <label for="cp-old" class="rl-label">{{ t('dashboard.password.oldLabel') }}</label>
                    <input
                        id="cp-old"
                        v-model="oldPassword"
                        type="password"
                        class="rl-input"
                        autocomplete="current-password"
                        :aria-invalid="passwordError ? 'true' : undefined"
                        aria-describedby="cp-old-error"
                    />
                    <InputError id="cp-old-error" :message="localize(passwordError)" variant="inline" />
                </div>

                <Link href="/forgot-password" class="rl-link rl-link--strong password__forgot">
                    {{ t('dashboard.password.forgot') }}
                </Link>

                <div class="rl-sheet__spacer"></div>
                <div class="password__actions">
                    <button type="button" class="rl-button password__primary" :disabled="!oldPassword || verifying" @click="emit('verify')">
                        {{ verifying ? t('dashboard.password.verifying') : t('dashboard.password.continue') }}
                    </button>
                    <button type="button" class="rl-button rl-button--secondary" @click="emit('back')">
                        {{ t('common.back') }}
                    </button>
                </div>
            </template>

            <!-- Step 2: choose the new password -->
            <template v-else>
                <h1 id="password-title" class="rl-title password__title">{{ t('dashboard.password.title2') }}</h1>
                <p class="password__subtitle">{{ t('dashboard.password.subtitle2') }}</p>

                <div class="rl-form">
                    <div class="rl-field">
                        <label for="cp-new" class="rl-label">{{ t('dashboard.password.newLabel') }}</label>
                        <input
                            id="cp-new"
                            v-model="newPassword"
                            type="password"
                            class="rl-input"
                            autocomplete="new-password"
                            :aria-invalid="newPasswordError ? 'true' : undefined"
                            aria-describedby="cp-new-error"
                        />
                        <InputError id="cp-new-error" :message="localize(newPasswordError)" variant="inline" />
                    </div>
                    <div class="rl-field">
                        <label for="cp-confirm" class="rl-label">{{ t('dashboard.password.confirmLabel') }}</label>
                        <input
                            id="cp-confirm"
                            v-model="confirmPassword"
                            type="password"
                            class="rl-input"
                            autocomplete="new-password"
                            :aria-invalid="confirmPasswordError ? 'true' : undefined"
                            aria-describedby="cp-confirm-error"
                        />
                        <InputError id="cp-confirm-error" :message="localize(confirmPasswordError)" variant="inline" />
                    </div>
                </div>

                <!-- Old-password / rate-limit errors from the save request (previously only rendered on step 1). -->
                <InputError :message="localize(passwordError)" variant="inline" class="password__general-error" />

                <div class="rl-sheet__spacer"></div>
                <div class="password__actions">
                    <button
                        type="button"
                        class="rl-button password__primary"
                        :disabled="!newPassword || !confirmPassword || updating"
                        @click="emit('update')"
                    >
                        {{ updating ? t('dashboard.password.updating') : t('dashboard.password.done') }}
                    </button>
                    <button type="button" class="rl-button rl-button--secondary" @click="emit('back')">
                        {{ t('common.back') }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>
/* Back arrow (chevron flipped to point back; flipped again in right-to-left). */
.password__back-icon :deep(svg) {
    transform: scaleX(-1);
}

[dir='rtl'] .password__back-icon :deep(svg) {
    transform: none;
}

.password__title {
    margin: 12px 0 8px;
    font-size: 44px;
}

.password__subtitle {
    margin: 0 0 22px;
    font-size: 15px;
    color: var(--rl-color-dialog-text);
}

.password__forgot {
    align-self: flex-start;
    margin-top: 14px;
    font-size: 15px;
    text-decoration: underline;
}

.password__general-error {
    margin-top: 14px;
}

.password__actions {
    display: flex;
    flex-shrink: 0;
    flex-direction: column;
    gap: 10px;
    margin-top: auto;
}

.password__primary {
    height: 54px;
    font-size: 21px;
}

.password__success {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
    padding: 12px 14px;
    border: 1px solid var(--rl-color-found);
    border-radius: 12px;
    background: var(--rl-color-found-wash);
    color: var(--rl-color-found-bright);
    font-size: 15px;
    font-weight: 600;
    animation: password-success 240ms ease-out;
}

.rl-sheet:lang(ar) .password__title {
    line-height: 1.2;
}

.rl-sheet:lang(ar) .rl-label,
.rl-sheet:lang(ar) .rl-sheet__brand {
    font-family: var(--rl-font-sans);
    font-size: 12px;
    letter-spacing: 0;
}

.rl-sheet:lang(ar) .rl-button {
    letter-spacing: 0;
}

@keyframes password-success {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: none; }
}

@media (prefers-reduced-motion: reduce) {
    .password__success {
        animation: none;
    }
}
</style>
