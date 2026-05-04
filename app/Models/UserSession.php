<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    /**
     * Mass-assignable attributes. UserSession rows are written by the
     * auth-event pipeline (login/logout). Public input never reaches them.
     */
    protected $fillable = [
        'user_id',
        'device_id',
        'started_at',
        'ended_at',
        'end_reason',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
