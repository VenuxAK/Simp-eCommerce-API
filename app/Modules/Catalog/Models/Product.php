<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Supplier\Models\Supplier;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a Product in the system.
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
}
