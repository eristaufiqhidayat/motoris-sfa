<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visit extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'checkin_at' => 'datetime',
            'stok_outlet' => 'array',
            'flags' => 'array',
            'diperiksa_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }
}
