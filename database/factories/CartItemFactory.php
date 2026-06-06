<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Models\CartItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
