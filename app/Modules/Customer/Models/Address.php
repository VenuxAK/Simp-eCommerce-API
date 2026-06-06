<?php

namespace App\Modules\Customer\Models;

use App\Modules\Core\Enums\AddressType;
use App\Modules\ECommerce\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'type' => AddressType::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
