<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Depot extends Model
{
    protected $guarded = [];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function gudang(): Location
    {
        return $this->locations()->where('tipe', Location::GUDANG)->firstOrFail();
    }

    public function karantina(): Location
    {
        return $this->locations()->where('tipe', Location::KARANTINA)->firstOrFail();
    }
}
