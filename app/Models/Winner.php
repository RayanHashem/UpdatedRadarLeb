<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Winner extends Model
{
    protected $guarded = [];

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
