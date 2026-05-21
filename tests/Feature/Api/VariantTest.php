<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
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

        $response = $this->getJson("/api/variants/by-sku/TEST-SKU-001", $this->headers);

        $response->assertOk()->assertJsonPath('variant.sku', 'TEST-SKU-001');
    }

    public function test_returns_404_for_unknown_sku(): void
    {
        $this->getJson('/api/variants/by-sku/DOES-NOT-EXIST', $this->headers)->assertNotFound();
    }
}
