<?php

namespace App\Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a Store in the system.
 */
class Store extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active', 'settings'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }
}
