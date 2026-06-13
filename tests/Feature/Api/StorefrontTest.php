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
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10]);

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
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10]);

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
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10]);

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
        $cat1 = Category::factory()->create(['store_id' => $this->store->id, 'slug' => 'tops', 'name' => 'Tops']);
        $cat2 = Category::factory()->create(['store_id' => $this->store->id, 'slug' => 'bottoms', 'name' => 'Bottoms']);

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
            ->getJson('/api/storefront/products?category_slug=' . $cat1->slug);

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

    // ─── Sorting ───────────────────────────────────────────────

    public function test_can_sort_products_by_price_ascending(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);

        $cheap = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Cheap Item', 'base_price' => 10,
        ]);
        ProductVariant::factory()->create(['product_id' => $cheap->id, 'stock_quantity' => 5]);

        $expensive = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Expensive Item', 'base_price' => 100,
        ]);
        ProductVariant::factory()->create(['product_id' => $expensive->id, 'stock_quantity' => 5]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products?sort_by=price&sort_dir=asc');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertEquals('Cheap Item', $names->first());
        $this->assertEquals('Expensive Item', $names->last());
    }

    public function test_can_sort_products_by_price_descending(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);

        $cheap = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Cheap Item', 'base_price' => 10,
        ]);
        ProductVariant::factory()->create(['product_id' => $cheap->id, 'stock_quantity' => 5]);

        $expensive = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Expensive Item', 'base_price' => 100,
        ]);
        ProductVariant::factory()->create(['product_id' => $expensive->id, 'stock_quantity' => 5]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products?sort_by=price&sort_dir=desc');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertEquals('Expensive Item', $names->first());
        $this->assertEquals('Cheap Item', $names->last());
    }

    public function test_default_sort_is_name_ascending(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);

        $b = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'B Item', 'base_price' => 50,
        ]);
        ProductVariant::factory()->create(['product_id' => $b->id, 'stock_quantity' => 5]);

        $a = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'A Item', 'base_price' => 50,
        ]);
        ProductVariant::factory()->create(['product_id' => $a->id, 'stock_quantity' => 5]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertEquals('A Item', $names->first());
        $this->assertEquals('B Item', $names->last());
    }

    // ─── Price Filter ──────────────────────────────────────────

    public function test_can_filter_products_by_min_price(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);

        $cheap = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Cheap', 'base_price' => 10,
        ]);
        ProductVariant::factory()->create(['product_id' => $cheap->id, 'stock_quantity' => 5]);

        $mid = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Mid', 'base_price' => 50,
        ]);
        ProductVariant::factory()->create(['product_id' => $mid->id, 'stock_quantity' => 5]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products?min_price=30');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mid');
    }

    public function test_can_filter_products_by_max_price(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);

        $mid = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Mid', 'base_price' => 50,
        ]);
        ProductVariant::factory()->create(['product_id' => $mid->id, 'stock_quantity' => 5]);

        $expensive = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Expensive', 'base_price' => 100,
        ]);
        ProductVariant::factory()->create(['product_id' => $expensive->id, 'stock_quantity' => 5]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products?max_price=70');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mid');
    }

    public function test_can_filter_products_by_price_range(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);

        $cheap = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Cheap', 'base_price' => 10,
        ]);
        ProductVariant::factory()->create(['product_id' => $cheap->id, 'stock_quantity' => 5]);

        $mid = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Mid', 'base_price' => 50,
        ]);
        ProductVariant::factory()->create(['product_id' => $mid->id, 'stock_quantity' => 5]);

        $expensive = Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Expensive', 'base_price' => 100,
        ]);
        ProductVariant::factory()->create(['product_id' => $expensive->id, 'stock_quantity' => 5]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products?min_price=30&max_price=70');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mid');
    }

    // ─── Extended Search ───────────────────────────────────────

    public function test_search_matches_product_name(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);
        Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Red Jacket', 'description' => 'A warm winter coat.',
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products?search=jacket');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_matches_product_description(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);
        Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Blue Shirt', 'description' => 'Made from organic cotton.',
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products?search=cotton');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_returns_empty_when_no_match(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);
        Product::factory()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id,
            'name' => 'Shoes',
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/products?search=xxxnonexistentxxx');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ─── Category Hierarchy ────────────────────────────────────

    public function test_categories_includes_parent_id(): void
    {
        Category::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Parent Cat',
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/categories');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'parent_id', 'children', 'products_count']]]);
    }

    public function test_categories_returns_nested_children(): void
    {
        $parent = Category::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Clothing',
            'slug' => 'clothing',
        ]);

        Category::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'T-Shirts',
            'slug' => 't-shirts',
            'parent_id' => $parent->id,
        ]);

        Category::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Jeans',
            'slug' => 'jeans',
            'parent_id' => $parent->id,
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/categories');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));

        $category = $response->json('data.0');
        $this->assertEquals('Clothing', $category['name']);
        $this->assertCount(2, $category['children']);
        $this->assertEquals('Jeans', $category['children'][0]['name']);
        $this->assertEquals('T-Shirts', $category['children'][1]['name']);
    }

    public function test_leaf_categories_have_empty_children(): void
    {
        Category::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Shoes',
            'slug' => 'shoes',
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/categories');

        $response->assertOk();
        $this->assertEmpty($response->json('data.0.children'));
    }

    public function test_category_includes_product_count(): void
    {
        $category = Category::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Tops',
            'slug' => 'tops',
        ]);

        Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
        ]);

        $response = $this->withHeaders($this->storeHeaders)
            ->getJson('/api/storefront/categories');

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.0.products_count'));
    }
}
