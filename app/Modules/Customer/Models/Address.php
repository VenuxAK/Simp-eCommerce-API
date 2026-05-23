<?php

namespace App\Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A physical shipping address belonging to a customer.
 *
 * One customer can have multiple addresses; one is designated as default.
 */
class Address extends Model
{
    protected $fillable = [
        'customer_id', 'type', 'name', 'phone',
        'street', 'city', 'state', 'postal_code', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
