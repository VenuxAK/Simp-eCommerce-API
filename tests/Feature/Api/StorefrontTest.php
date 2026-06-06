<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private array $storeHeaders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create([
            'slug' => 'clothing',
            'name' => 'Clothing Store',
            'is_active' => true,
            'settings' => ['currency' => 'MMK', 'theme' => 'minimal'],
        ]);

        $this->storeHeaders = ['X-Store' => 'clothing'];
    }

    public function test_can_list_products(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_products_are_scoped_by_store(): void
    {
        $otherStore = Store::factory()->create(['slug' => 'other', 'is_active' => true]);
        Category::factory()->create(['store_id' => $otherStore->id, 'name' => 'Other Cat']);

        $category = Category::factory()->create(['store_id' => $this->store->id]);
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        Product::factory()->create([
            'store_id' => $otherStore->id,
            'category_id' => Category::where('store_id', $otherStore->id)->first()->id,
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_product_by_slug(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'slug' => 'blue-tshirt',
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products/blue-tshirt');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'blue-tshirt');
    }

    public function test_can_list_categories(): void
    {
        Category::factory()->create(['store_id' => $this->store->id, 'name' => 'Tops']);
        Category::factory()->create(['store_id' => $this->store->id, 'name' => 'Bottoms']);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/categories');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_categories_are_scoped_by_store(): void
    {
        $otherStore = Store::factory()->create(['slug' => 'other', 'is_active' => true]);
        Category::factory()->create(['store_id' => $otherStore->id, 'name' => 'Electronics']);

        Category::factory()->create(['store_id' => $this->store->id, 'name' => 'Clothing']);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/categories');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Clothing');
    }

    public function test_can_get_store_settings(): void
    {
        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/settings');

        $response->assertOk()
            ->assertJsonPath('data.name', 'Clothing Store')
            ->assertJsonPath('data.slug', 'clothing')
            ->assertJsonStructure(['data' => ['id', 'name', 'slug', 'settings']]);
    }

    public function test_inactive_store_returns_404(): void
    {
        Store::factory()->create(['slug' => 'inactive', 'is_active' => false]);

        $response = $this->withHeaders(['X-Store' => 'inactive'])
            ->getJson('/api/storefront/products');

        $response->assertNotFound();
    }

    public function test_nonexistent_store_returns_404(): void
    {
        $response = $this->withHeaders(['X-Store' => 'nonexistent'])
            ->getJson('/api/storefront/products');

        $response->assertNotFound();
    }

    public function test_can_search_products(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);
        Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'name' => 'Blue T-Shirt',
        ]);
        Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'name' => 'Red Dress',
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products?search=shirt');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_products_by_category(): void
    {
        $cat1 = Category::factory()->create(['store_id' => $this->store->id, 'name' => 'Tops']);
        $cat2 = Category::factory()->create(['store_id' => $this->store->id, 'name' => 'Bottoms']);

        Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $cat1->id,
            'name' => 'Shirt',
        ]);
        Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $cat2->id,
            'name' => 'Jeans',
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products?category_id='.$cat1->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Shirt');
    }

    public function test_can_get_products_without_variants(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);
        Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
