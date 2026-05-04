<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    /**
     * Mass-assignable attributes. A Device is created/updated by the
     * fingerprint capture pipeline; the values it stores all originate
     * from request headers we already trust enough to log.
     */
    protected $fillable = [
        'user_id',
        'device_hash',
        'platform',
        'user_agent',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }
}
