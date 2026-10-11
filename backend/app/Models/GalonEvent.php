<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalonEvent extends Model
{
    protected $guarded = [];

    public function galon(): BelongsTo
    {
        return $this->belongsTo(Galon::class);
    }
}
