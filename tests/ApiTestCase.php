<?php

namespace Tests;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $staffUser;
    protected array $adminHeaders;
    protected array $staffHeaders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->staffUser = User::factory()->create(['role' => 'staff']);
        $this->adminHeaders = ['Authorization' => "Bearer {$this->adminUser->createToken('test')->plainTextToken}"];
        $this->staffHeaders = ['Authorization' => "Bearer {$this->staffUser->createToken('test')->plainTextToken}"];
    }

    protected function createVariant(int $stockQty = 10, float $price = 50): ProductVariant
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'base_price' => $price]);
        return ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => $stockQty,
            'price_adjustment' => 0,
        ]);
    }
}
