<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['Black', 'White', 'Red', 'Blue', 'Green', 'Gray', 'Navy', 'Pink'];

        return [
            'product_id' => Product::factory(),
            'sku' => 'SKU-'.Str::upper(Str::random(12)),
            'size' => fake()->randomElement($sizes),
            'color' => fake()->randomElement($colors),
            'price_adjustment' => fake()->randomFloat(2, -10, 20),
            'stock_quantity' => fake()->numberBetween(0, 100),
        ];
    }
}
