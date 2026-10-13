/**
 * Password rules shared by Sign up and Change password, so both ask for
 * exactly the same thing. They mirror the server's Password::defaults()
 * (AppServiceProvider), which both /register and /settings/password use:
 * at least 8 characters, an uppercase and a lowercase letter, a number and
 * a symbol. Labels are Sign up's English wording; Change password shows the
 * translated `dashboard.password.rule.<id>` strings instead.
 */
export const PASSWORD_RULES = [
    { id: 'min', label: 'At least 8 characters', test: (p: string) => p.length >= 8 },
    { id: 'upper', label: 'At least one uppercase letter', test: (p: string) => /[A-Z]/.test(p) },
    { id: 'lower', label: 'At least one lowercase letter', test: (p: string) => /[a-z]/.test(p) },
    { id: 'number', label: 'At least one number', test: (p: string) => /\d/.test(p) },
    { id: 'special', label: 'At least one special character (!@#$%^&*)', test: (p: string) => /[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(p) },
] as const;

export type PasswordRule = (typeof PASSWORD_RULES)[number];

/** The rules a password doesn't meet yet (empty when it's valid). */
export function failedPasswordRules(password: string): PasswordRule[] {
    return PASSWORD_RULES.filter((rule) => !rule.test(password));
}
