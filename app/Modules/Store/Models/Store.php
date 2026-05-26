<?php

namespace App\Modules\Store\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'domain', 'description', 'logo', 'phone', 'email', 'is_active', 'settings'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    protected static function newFactory(): StoreFactory
    {
        return StoreFactory::new();
    }
}
