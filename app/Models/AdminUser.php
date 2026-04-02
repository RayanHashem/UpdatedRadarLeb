<?php

namespace App\Models;

/**
 * Thin wrapper for admin-panel authentication.
 *
 * The main User model overrides getAuthIdentifierName() → 'phone_number'
 * because the public app authenticates by phone. Admin users don't have
 * phone numbers, so the admin guard needs to identify by 'id' instead.
 *
 * Same table, same casts, same relationships — only the auth identifier differs.
 */
class AdminUser extends User
{
    protected $table = 'users';

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }
}
