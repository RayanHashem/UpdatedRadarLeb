<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * Explicit allowlist instead of `$guarded = []`. Every column on this
     * table represents money or audit metadata — keep the surface tight.
     * The `id` and `created_at` / `updated_at` columns are managed by
     * Eloquent itself.
     */
    protected $fillable = [
        'user_id',
        'game_id',
        'scan_id',
        'type',
        'amount',
        'balance_after',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure type is always lowercase snake_case
        static::saving(function ($transaction) {
            if (isset($transaction->type)) {
                $transaction->type = strtolower($transaction->type);
            }
        });

        // Validate amount matches scan cost when scan_id is present
        static::saving(function ($transaction) {
            if ($transaction->scan_id && $transaction->type === 'debit' && !$transaction->exists) {
                // Only validate on create (not update) to avoid issues with existing data
                $scan = \App\Models\Scan::find($transaction->scan_id);
                if ($scan && abs($transaction->amount - $scan->cost) > 0.01) {
                    throw new \InvalidArgumentException(
                        "Wallet transaction amount ({$transaction->amount}) must match scan cost ({$scan->cost})"
                    );
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function scan()
    {
        return $this->belongsTo(Scan::class);
    }

    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    public function scopeTopups($query)
    {
        return $query->where('type', 'topup');
    }
}
