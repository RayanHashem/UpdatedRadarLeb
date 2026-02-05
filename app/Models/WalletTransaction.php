<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
