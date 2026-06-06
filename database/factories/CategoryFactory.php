<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Category;
use App\Modules\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'T-Shirts', 'Pants', 'Dresses', 'Jackets',
            'Shorts', 'Skirts', 'Hoodies', 'Accessories',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'store_id' => Store::inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
