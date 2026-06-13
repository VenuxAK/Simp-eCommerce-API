<?php

namespace App\Modules\Catalog\Models;

use App\Modules\ECommerce\Models\CartItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Sales\Models\OrderItem;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SKU-level variant belonging to a parent product.
 *
 * Each variant carries its own price adjustment (delta from base_price),
 * stock quantity, and optional size/color attributes. This is the entity
 * that flows through carts, orders, and inventory movements.
 */
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'sku', 'size', 'color', 'image', 'price_adjustment', 'purchase_price', 'stock_quantity', 'low_stock_threshold'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    protected static function newFactory(): ProductVariantFactory
    {
        return ProductVariantFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'product_variant_id');
    }

    // TODO: Replace with contract when Sales module is extracted.
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // TODO: Replace with contract when Inventory module is extracted.
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_variant_id');
    }
}
