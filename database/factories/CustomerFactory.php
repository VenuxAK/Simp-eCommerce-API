<?php

namespace Database\Factories;

use App\Modules\Customer\Models\Customer;
use App\Modules\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'loyalty_points' => fake()->numberBetween(0, 500),
            'store_id' => Store::inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
