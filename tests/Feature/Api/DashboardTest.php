<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];
    }

    public function test_dashboard_returns_summary(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 2]);

        $response = $this->getJson('/api/dashboard/summary', $this->headers);

        $response->assertOk()->assertJsonStructure([
            'today_sales', 'today_orders_count', 'total_products',
            'total_variants', 'low_stock_count', 'out_of_stock_count',
            'low_stock_variants', 'recent_orders',
        ]);
    }

    public function test_dashboard_shows_low_stock_alerts(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 3]);

        $response = $this->getJson('/api/dashboard/summary', $this->headers);

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, $response->json('low_stock_count'));
    }
}
