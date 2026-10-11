<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Galon extends Model
{
    public const MAKS_SIKLUS = 60;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal_produksi' => 'date',
            'last_moved_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(GalonEvent::class)->orderBy('id');
    }

    public function posisi(): string
    {
        if ($this->outlet_id) {
            return 'Di '.$this->outlet->nama;
        }

        return $this->location ? 'Di '.$this->location->nama : 'Tidak diketahui';
    }

    public function mendekatiBatas(): bool
    {
        return $this->siklus >= self::MAKS_SIKLUS - 4;
    }
}
