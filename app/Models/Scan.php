<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'success',
        'radar_level',
        'cost',
        'latitude',
        'longitude',
        'location_accuracy',
        'location_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'cost' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'location_accuracy' => 'decimal:2',
            'location_at' => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function game() { return $this->belongsTo(Game::class); }
}
