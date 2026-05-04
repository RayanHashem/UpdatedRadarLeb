<?php

namespace App\Console\Commands;

use App\Actions\Wallet\TopOffWallet;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Credit a user's wallet from the CLI. Useful for:
 *   - giving yourself radar cash to test scans / wins locally
 *   - manual top-ups when the Filament admin panel isn't handy
 *   - seeding test accounts during a stage demo
 *
 * Wraps the same `TopOffWallet` action class the Filament admin panel and
 * the seeders use, so the same invariants hold:
 *   - amount > 0 (rejected otherwise)
 *   - one wallet_transactions row written, type 'topup', game_id NULL
 *   - balance update is atomic with the transaction insert
 *
 * Looks up the user by phone number (the login credential) so you don't
 * need to remember IDs. Phone numbers are stored as plain strings and
 * matched exactly.
 *
 * Usage:
 *   php artisan wallet:topup 96171234567 100
 *   php artisan wallet:topup user@example.com 50 --notes="Stage demo prep"
 *
 * The first argument accepts a phone number OR an email — if the value
 * contains '@' it's treated as an email lookup, otherwise as a phone.
 */
class TopUpWallet extends Command
{
    protected $signature = 'wallet:topup
        {identifier : Phone number OR email of the target user}
        {amount : Radar cash to credit (positive number)}
        {--notes= : Optional notes recorded on the wallet_transactions row}';

    protected $description = 'Credit a user wallet via the TopOffWallet action (writes a topup transaction row). Accepts phone or email.';

    public function handle(TopOffWallet $topOff): int
    {
        $identifier = trim((string) $this->argument('identifier'));
        $amount     = (float) $this->argument('amount');
        $notes      = (string) ($this->option('notes') ?? 'CLI top-up');

        if ($identifier === '') {
            $this->error('Phone or email is required.');
            return self::FAILURE;
        }

        if ($amount <= 0) {
            $this->error('Amount must be greater than zero.');
            return self::FAILURE;
        }

        // Phone numbers are digit-only in this app; if the identifier looks
        // like an email, look up by email (case-insensitive). Otherwise
        // treat it as a phone. This keeps the CLI ergonomic without needing
        // a separate `--email` flag.
        $isEmail = str_contains($identifier, '@');

        $user = $isEmail
            ? User::whereRaw('LOWER(email) = ?', [strtolower($identifier)])->first()
            : User::where('phone_number', $identifier)->first();

        if (! $user) {
            $field = $isEmail ? 'email' : 'phone';
            $this->error("No user found with {$field} '{$identifier}'.");
            $this->line('Tip: phone numbers and emails are stored exactly as entered at registration.');
            return self::FAILURE;
        }

        $balanceBefore = (float) $user->wallet_balance;

        // The action handles the DB::transaction + invariant checks. If
        // anything throws, the transaction rolls back and we surface the
        // error to the operator instead of swallowing it.
        try {
            $topOff($user, $amount, $notes);
        } catch (\Throwable $e) {
            $this->error('Top-up failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $user->refresh();

        $this->info(sprintf(
            '+%s credited to %s (%s %s).',
            number_format($amount, 2),
            $user->name ?: '(no name)',
            $isEmail ? 'email' : 'phone',
            $identifier,
        ));
        $this->line(sprintf(
            'Balance: %s → %s',
            number_format($balanceBefore, 2),
            number_format((float) $user->wallet_balance, 2),
        ));

        return self::SUCCESS;
    }
}
