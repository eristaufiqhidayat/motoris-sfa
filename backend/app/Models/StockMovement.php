<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $guarded = [];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
