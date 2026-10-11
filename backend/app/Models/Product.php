<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public const GALON = 'GLN19';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['returnable' => 'boolean'];
    }

    public static function galon(): self
    {
        return static::where('kode', self::GALON)->firstOrFail();
    }
}
