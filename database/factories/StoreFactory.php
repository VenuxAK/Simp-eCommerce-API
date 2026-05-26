<?php

namespace Database\Factories;

use App\Modules\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(1),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
