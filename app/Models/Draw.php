<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Draw extends Model
{
    /**
     * Mass-assignable attributes. Draws are administrative records — opened
     * by seeders / Filament actions, closed by the cron-driven draw runner.
     * Never written from public user input.
     */
    protected $fillable = [
        'game_id',
        'draw_number',
        'status',
        'opened_at',
        'closed_at',
        'winner_user_id',
        'winning_scan_id',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function winningScan()
    {
        return $this->belongsTo(Scan::class, 'winning_scan_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}
