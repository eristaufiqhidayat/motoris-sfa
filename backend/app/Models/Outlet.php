<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rata_jual_mingguan' => 'array',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(SalesRoute::class, 'sales_route_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function sisaLimit(): int
    {
        return max(0, $this->limit_kredit - $this->piutang);
    }

    public function bolehTempo(): bool
    {
        return $this->limit_kredit > 0;
    }
}
