<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loading extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'muat' => 'array',
            'selisih' => 'array',
            'setor_at' => 'datetime',
            'diterima_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(SalesRoute::class, 'sales_route_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'vehicle_location_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /** Uang per metode bayar dari penjualan canvas di loading ini. */
    public function uang(): array
    {
        $rows = $this->orders()->where('tipe', SalesOrder::CANVAS)
            ->selectRaw('metode_bayar, sum(total) as total')
            ->groupBy('metode_bayar')->pluck('total', 'metode_bayar');

        return [
            'tunai' => (int) ($rows['tunai'] ?? 0),
            'qris' => (int) ($rows['qris'] ?? 0),
            'tempo' => (int) ($rows['tempo'] ?? 0),
        ];
    }
}
