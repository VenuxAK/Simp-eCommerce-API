<?php

namespace App\Modules\ECommerce\Models;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A quantity of a product variant held in a customer's shopping cart.
 *
 * Identified by customer_id (authenticated) or session_id (guest).
 */
class CartItem extends Model
{
    protected $fillable = [
        'customer_id', 'session_id', 'product_variant_id', 'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
