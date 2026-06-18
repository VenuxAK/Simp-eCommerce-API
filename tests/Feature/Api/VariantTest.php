<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Identity\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('inventory_staff');
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];
    }

    public function test_can_lookup_variant_by_sku(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU-001',
        ]);

        $response = $this->getJson('/api/variants/by-sku/TEST-SKU-001', $this->headers);

        $response->assertOk()->assertJsonPath('variant.sku', 'TEST-SKU-001');
    }

    public function test_returns_404_for_unknown_sku(): void
    {
        $this->getJson('/api/variants/by-sku/DOES-NOT-EXIST', $this->headers)->assertNotFound();
    }

    // ─── Stock & Threshold ─────────────────────────────────────

    public function test_can_update_stock(): void
    {
        $variant = $this->createVariant();
        $initial = $variant->stock_quantity;

        $response = $this->patchJson(
            "/api/variants/{$variant->id}/stock",
            ['quantity' => $initial + 10],
            $this->headers,
        );

        $response->assertOk()->assertJsonPath('data.stock_quantity', $initial + 10);
    }

    public function test_can_update_low_stock_threshold(): void
    {
        $variant = $this->createVariant();

        $response = $this->patchJson(
            "/api/variants/{$variant->id}/stock",
            ['quantity' => $variant->stock_quantity, 'low_stock_threshold' => 20],
            $this->headers,
        );

        $response->assertOk()->assertJsonPath('data.low_stock_threshold', 20);
    }

    public function test_threshold_must_be_non_negative(): void
    {
        $variant = $this->createVariant();

        $response = $this->patchJson(
            "/api/variants/{$variant->id}/stock",
            ['quantity' => $variant->stock_quantity, 'low_stock_threshold' => -1],
            $this->headers,
        );

        $response->assertUnprocessable()->assertJsonValidationErrors(['low_stock_threshold']);
    }

    public function test_threshold_defaults_to_10(): void
    {
        $variant = $this->createVariant();

        $this->assertEquals(10, $variant->fresh()->low_stock_threshold);
    }

    private function createVariant(): ProductVariant
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        return ProductVariant::factory()->create(['product_id' => $product->id]);
    }
}
