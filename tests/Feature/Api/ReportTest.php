<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'base_price' => 50]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10, 'price_adjustment' => 0]);

        $this->postJson('/api/v1/orders', [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 3]],
            'payment' => ['method' => 'cash', 'amount' => 150],
        ], $this->headers);
    }

    public function test_sales_report(): void
    {
        $response = $this->getJson('/api/v1/reports/sales', $this->headers);
        $response->assertOk()->assertJsonStructure(['total_sales', 'order_count', 'average_order_value', 'items_sold']);
        $this->assertEquals(150, $response->json('total_sales'));
    }

    public function test_best_sellers_report(): void
    {
        $response = $this->getJson('/api/v1/reports/best-sellers', $this->headers);
        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
        $this->assertEquals(3, $response->json('data.0.total_qty'));
    }
}
