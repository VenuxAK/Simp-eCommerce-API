<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$this->user->createToken('test')->plainTextToken}"];
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

    public function test_low_stock_uses_configurable_threshold(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        // Variant with stock=15 and default threshold=10 — NOT low stock.
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 15,
        ]);

        // Variant with stock=8 and threshold=5 — NOT low stock (stock > threshold).
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 8,
            'low_stock_threshold' => 5,
        ]);

        // Variant with stock=3 and threshold=10 — IS low stock (stock <= threshold).
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 3,
            'low_stock_threshold' => 10,
        ]);

        // Variant with stock=1 and threshold=0 — NOT low stock (threshold=0 disables).
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 1,
            'low_stock_threshold' => 0,
        ]);

        $response = $this->getJson('/api/dashboard/summary', $this->headers);

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(4, $data['total_variants']);
        $this->assertEquals(1, $data['low_stock_count'], 'Only variant with stock 3 <= threshold 10 should be low stock.');
        $this->assertEquals(0, $data['out_of_stock_count'], 'No variants have stock_quantity = 0');
    }

    public function test_low_stock_count_is_zero_when_threshold_disabled(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        // All variants have threshold=0 (disabled).
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 3,
            'low_stock_threshold' => 0,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 1,
            'low_stock_threshold' => 0,
        ]);

        $response = $this->getJson('/api/dashboard/summary', $this->headers);

        $response->assertOk();
        $this->assertEquals(0, $response->json('low_stock_count'));
    }
}
