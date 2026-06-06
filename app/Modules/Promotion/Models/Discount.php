<?php

namespace App\Modules\Promotion\Models;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Core\Enums\DiscountScope;
use App\Modules\Core\Enums\DiscountType;
use App\Modules\Store\Models\Store;
use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a Discount in the system.
 */
class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'value', 'applies_to',
        'category_id', 'product_id', 'store_id', 'starts_at', 'ends_at', 'is_active',
    ];

    protected static function newFactory(): DiscountFactory
    {
        return DiscountFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
            'type' => DiscountType::class,
            'applies_to' => DiscountScope::class,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
