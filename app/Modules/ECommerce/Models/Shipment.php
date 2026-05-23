<?php

namespace App\Modules\ECommerce\Models;

use App\Modules\Customer\Models\Address;
use App\Modules\Sales\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks physical delivery of an online order.
 *
 * Created at checkout, updated when the order is shipped and delivered.
 */
class Shipment extends Model
{
    protected $fillable = [
        'order_id', 'address_id', 'method',
        'tracking_number', 'tracking_url',
        'shipped_at', 'delivered_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }
}
