<?php

namespace App\Modules\Promotion\Models;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discount extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'type', 'value', 'applies_to',
        'category_id', 'product_id', 'starts_at', 'ends_at', 'is_active',
    ];

    protected static function newFactory(): DiscountFactory
    {
        return DiscountFactory::new();
    }

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
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
