<?php

namespace App\Modules\Catalog\Models;

use App\Modules\ECommerce\Models\CartItem;
use App\Modules\ECommerce\Models\WishlistItem;
use App\Modules\Promotion\Models\Discount;
use App\Modules\Store\Models\Store;
use App\Modules\Supplier\Models\Supplier;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Central catalog entity representing a sellable product.
 *
 * Products act as containers for one or more SKU-level variants
 * that carry actual pricing and inventory. A product without variants
 * is treated as a simple (non-configurable) item.
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = ['category_id', 'supplier_id', 'store_id', 'name', 'slug', 'description', 'base_price', 'image'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
        ];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // TODO: Replace with contract when Supplier module is extracted.
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'product_variant_id', 'id');
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }
}
