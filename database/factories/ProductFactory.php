<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Classic Cotton Tee', 'Slim Fit Chinos', 'Floral Summer Dress',
            'Denim Jacket', 'Cargo Shorts', 'Pleated Skirt',
            'Zip-Up Hoodie', 'Silk Scarf', 'Striped Polo',
            'Linen Blazer', 'High-Waist Trousers', 'Graphic Sweatshirt',
        ]).' '.fake()->randomLetter();

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'description' => fake()->paragraph(),
            'base_price' => fake()->randomFloat(2, 5, 150),
            'store_id' => 1,
            'image' => null,
        ];
    }
}
