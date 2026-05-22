<?php

namespace Database\Factories;

use App\Modules\Customer\Models\Customer;
use App\Modules\Sales\Models\Order;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'order_number' => 'ORD-' . fake()->unique()->numerify('########'),
            'total_amount' => 0,
            'status' => fake()->randomElement(['pending', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
