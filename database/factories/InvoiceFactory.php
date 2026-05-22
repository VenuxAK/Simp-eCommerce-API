<?php

namespace Database\Factories;

use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'invoice_number' => 'INV-' . fake()->unique()->numerify('########'),
            'issued_date' => fake()->date(),
            'due_date' => fake()->optional()->dateTimeThisMonth(),
            'status' => fake()->randomElement(['draft', 'issued', 'paid', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
            'terms' => 'Payment due within 30 days.',
        ];
    }
}
