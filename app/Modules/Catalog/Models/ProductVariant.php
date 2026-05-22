<?php

namespace App\Modules\Catalog\Models;

use App\Models\OrderItem;
use App\Models\StockMovement;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'sku', 'size', 'color', 'image', 'price_adjustment', 'purchase_price', 'stock_quantity'];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'stock_quantity' => 'integer',
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
