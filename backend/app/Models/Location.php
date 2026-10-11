<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    public const GUDANG = 'gudang';
    public const KARANTINA = 'karantina';
    public const KENDARAAN = 'kendaraan';

    protected $guarded = [];

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function qty(Product $product, string $kondisi = 'isi'): int
    {
        return (int) $this->balances()
            ->where('product_id', $product->id)
            ->where('kondisi', $kondisi)
            ->value('qty');
    }
}
