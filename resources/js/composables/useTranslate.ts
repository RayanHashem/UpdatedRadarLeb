import { computed, type ComputedRef } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Inertia shared-prop shape for i18n. Mirrors what HandleInertiaRequests::share
 * pushes: a flat key → string map, plus the active locale and the supported
 * locales registry.
 */
export type TranslationsPayload = {
    locale: string
    locales: Record<string, string>
    translations: Record<string, string>
    contact?: { phone?: string }
}

/**
 * Tiny i18n composable. Reads the translation map straight off Inertia's
 * shared props — no async loading, no extra HTTP roundtrip. Supports
 * `{placeholder}` interpolation for runtime values (we lean on this for
 * things like the support phone, which lives in config and is shared as
 * `contact.phone`).
 *
 * Usage in a `<script setup lang="ts">` block:
 *
 *   const { t, locale } = useTranslate()
 *   const greeting = t('common.signIn')                            // "Sign in" / "تسجيل الدخول"
 *   const help     = t('dashboard.help.step2', { phone: '...' })   // interpolation
 *
 * Returning the key itself when a translation is missing is intentional —
 * it surfaces the gap in the UI instead of silently rendering an empty
 * string, which makes "what's still untranslated?" obvious at a glance.
 */
export function useTranslate() {
    const page = usePage<{
        locale: string
        locales: Record<string, string>
        translations: Record<string, string>
        contact?: { phone?: string }
    }>()

    const locale: ComputedRef<string> = computed(() => page.props.locale ?? 'en')
    const locales: ComputedRef<Record<string, string>> = computed(() => page.props.locales ?? { en: 'English' })

    function t(key: string, replacements: Record<string, string | number> = {}): string {
        const map = page.props.translations ?? {}
        let value = map[key] ?? key

        // Replace {placeholder} tokens — keeps key surface small while still
        // allowing dynamic values like the support phone.
        for (const [k, v] of Object.entries(replacements)) {
            value = value.replace(new RegExp(`\\{\\s*${k}\\s*\\}`, 'g'), String(v))
        }
        return value
    }

    return { t, locale, locales }
}

/**
 * Return value: 'rtl' if the active locale is right-to-left, else 'ltr'.
 *
 * Bind to the page root to get correct directionality without per-element
 * `dir="rtl"` attributes:
 *
 *   <div :dir="dir">…</div>
 */
export function useDirection(): ComputedRef<'rtl' | 'ltr'> {
    const RTL_LOCALES = new Set(['ar', 'he', 'fa', 'ur'])
    const { locale } = useTranslate()
    return computed(() => (RTL_LOCALES.has(locale.value) ? 'rtl' : 'ltr'))
}
