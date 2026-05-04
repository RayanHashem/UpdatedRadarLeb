<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Winner extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes. Winner rows are recorded by the scan
     * pipeline / admin actions only — never from user input — so this list
     * is more for documentation than defence. Still explicit > implicit.
     */
    protected $fillable = [
        'game_id',
        'game_name',
        'user_id',
        'user_name',
        'winner_user_id',
        'scan_id',
        'won_at',
    ];

    protected function casts(): array
    {
        return [
            'won_at' => 'datetime',
        ];
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function scan()
    {
        return $this->belongsTo(Scan::class);
    }
}
