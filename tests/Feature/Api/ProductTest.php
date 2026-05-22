<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Tests\ApiTestCase;

class ProductTest extends ApiTestCase
{

    public function test_can_list_products(): void
    {
        $category = Category::factory()->create();
        Product::factory(3)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/products', $this->adminHeaders);

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_create_product_with_variants(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Classic Tee',
            'base_price' => 29.99,
            'variants' => [
                ['sku' => 'TEE-BLK-M', 'size' => 'M', 'color' => 'Black', 'stock_quantity' => 10],
                ['sku' => 'TEE-WHT-L', 'size' => 'L', 'color' => 'White', 'stock_quantity' => 5],
            ],
        ], $this->adminHeaders);

        $response->assertCreated()->assertJsonPath('data.name', 'Classic Tee');
        $this->assertDatabaseHas('product_variants', ['sku' => 'TEE-BLK-M']);
    }

    public function test_product_requires_at_least_one_variant(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'No Variants',
            'base_price' => 10,
            'variants' => [],
        ], $this->adminHeaders);

        $response->assertUnprocessable();
    }

    public function test_variant_sku_must_be_unique(): void
    {
        $category = Category::factory()->create();
        ProductVariant::factory()->create(['sku' => 'DUP-SKU']);

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Duplicate SKU',
            'base_price' => 10,
            'variants' => [['sku' => 'DUP-SKU', 'stock_quantity' => 1]],
        ], $this->adminHeaders);

        $response->assertUnprocessable();
    }

    public function test_can_show_product(): void
    {
        $product = Product::factory()->has(ProductVariant::factory(2), 'variants')->create();

        $response = $this->getJson("/api/products/{$product->id}", $this->adminHeaders);

        $response->assertOk()->assertJsonPath('data.id', $product->id);
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'New Name',
        ], $this->adminHeaders);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_can_update_product_variants_in_place(): void
    {
        $product = Product::factory()->has(ProductVariant::factory(), 'variants')->create();
        $variant = $product->variants()->first();

        $response = $this->putJson("/api/products/{$product->id}", [
            'variants' => [
                ['id' => $variant->id, 'sku' => 'UPDATED-SKU', 'stock_quantity' => 20],
            ],
        ], $this->adminHeaders);

        $response->assertOk();
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'sku' => 'UPDATED-SKU']);
    }

    public function test_cannot_delete_product_with_order_history(): void
    {
        $product = Product::factory()->has(ProductVariant::factory(), 'variants')->create();
        $variant = $product->variants()->first();
        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id, 'product_variant_id' => $variant->id]);

        $response = $this->deleteJson("/api/products/{$product->id}", [], $this->adminHeaders);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_can_delete_product_without_orders(): void
    {
        $product = Product::factory()->has(ProductVariant::factory(), 'variants')->create();

        $response = $this->deleteJson("/api/products/{$product->id}", [], $this->adminHeaders);

        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_can_update_variant_stock(): void
    {
        $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);

        $response = $this->patchJson("/api/variants/{$variant->id}/stock", [
            'quantity' => 20,
        ], $this->adminHeaders);

        $response->assertOk();
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 20]);
    }

    public function test_cannot_set_negative_stock(): void
    {
        $variant = ProductVariant::factory()->create();

        $response = $this->patchJson("/api/variants/{$variant->id}/stock", [
            'quantity' => -5,
        ], $this->adminHeaders);

        $response->assertUnprocessable();
    }

    public function test_staff_cannot_create_product(): void
    {
        $category = Category::factory()->create();
        $response = $this->postJson('/api/products', [
            'category_id' => $category->id, 'name' => 'Staff Product',
            'base_price' => 10, 'variants' => [['sku' => 'STAFF', 'stock_quantity' => 1]],
        ], $this->staffHeaders);
        $response->assertForbidden();
    }

    public function test_staff_cannot_update_product(): void
    {
        $product = Product::factory()->create();
        $response = $this->putJson("/api/products/{$product->id}", ['name' => 'Hacked'], $this->staffHeaders);
        $response->assertForbidden();
    }

    public function test_staff_cannot_delete_product(): void
    {
        $product = Product::factory()->create();
        $response = $this->deleteJson("/api/products/{$product->id}", [], $this->staffHeaders);
        $response->assertForbidden();
    }

    public function test_staff_cannot_import_csv(): void
    {
        $file = UploadedFile::fake()->createWithContent('products.csv', "name,sku\nTest,TST");
        $response = $this->postJson('/api/products/import/csv', [
            'file' => $file,
        ], $this->staffHeaders);
        $response->assertForbidden();
    }

    public function test_csv_import_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('products.pdf', 100);
        $response = $this->postJson('/api/products/import/csv', [
            'file' => $file,
        ], $this->adminHeaders);
        $response->assertUnprocessable();
    }

    public function test_csv_import_rejects_row_with_missing_name(): void
    {
        $csv = "name,sku,base_price\n,NO-NAME,10\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);
        $response = $this->postJson('/api/products/import/csv', [
            'file' => $file,
        ], $this->adminHeaders);
        $response->assertOk();
        $this->assertEquals(0, $response->json('created'));
    }

    public function test_csv_import_rejects_negative_price(): void
    {
        $csv = "name,sku,base_price\nBad Product,NEG,-5\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);
        $response = $this->postJson('/api/products/import/csv', [
            'file' => $file,
        ], $this->adminHeaders);
        $response->assertOk();
        $this->assertEquals(0, $response->json('created'));
    }

    public function test_csv_import_succeeds_with_valid_data(): void
    {
        $csv = "name,sku,base_price,stock\nValid Product,VP-001,29.99,10\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);
        $response = $this->postJson('/api/products/import/csv', [
            'file' => $file,
        ], $this->adminHeaders);
        $response->assertOk();
        $this->assertEquals(1, $response->json('created'));
        $this->assertDatabaseHas('products', ['name' => 'Valid Product']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'VP-001']);
    }

    public function test_update_product_rejects_negative_variant_price_adjustment(): void
    {
        $product = Product::factory()->has(ProductVariant::factory(), 'variants')->create();
        $variant = $product->variants()->first();

        $response = $this->putJson("/api/products/{$product->id}", [
            'variants' => [
                ['id' => $variant->id, 'sku' => 'NEG-ADJ', 'price_adjustment' => -5],
            ],
        ], $this->adminHeaders);

        $response->assertUnprocessable();
    }

    public function test_update_product_rejects_negative_variant_stock(): void
    {
        $product = Product::factory()->has(ProductVariant::factory(), 'variants')->create();
        $variant = $product->variants()->first();

        $response = $this->putJson("/api/products/{$product->id}", [
            'variants' => [
                ['id' => $variant->id, 'sku' => 'NEG-STOCK', 'stock_quantity' => -1],
            ],
        ], $this->adminHeaders);

        $response->assertUnprocessable();
    }
}
