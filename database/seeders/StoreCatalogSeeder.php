<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Store\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreCatalogSeeder extends Seeder
{
    /**
     * Seed the catalog for a given store.
     */
    public function run(Store $store): void
    {
        // Skip if this store already has products seeded.
        if (Product::where('store_id', $store->id)->exists()) {
            return;
        }

        $catalogData = require database_path('data/products.php');

        // Track created brands and categories to avoid duplicates
        $brands = [];
        $categories = [];

        foreach ($catalogData as $item) {
            // 1. Create or retrieve Brand
            $brandName = $item['brand'];
            if (!isset($brands[$brandName])) {
                $brands[$brandName] = Brand::create([
                    'name' => $brandName,
                    'slug' => Str::slug($brandName) . '-' . $store->slug,
                    'logo' => $item['brand_logo'] ?? null,
                    'store_id' => $store->id,
                ]);
            }
            $brand = $brands[$brandName];

            // 2. Create or retrieve Category
            $categoryName = $item['category'];
            if (!isset($categories[$categoryName])) {
                $categories[$categoryName] = Category::create([
                    'name' => $categoryName,
                    'slug' => Str::slug($categoryName) . '-' . $store->slug,
                    'description' => "Shop $categoryName",
                    'store_id' => $store->id,
                ]);
            }
            $category = $categories[$categoryName];

            // 3. Create Product
            $product = Product::create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'store_id' => $store->id,
                'name' => $item['name'],
                'slug' => Str::slug($item['name']) . '-' . Str::random(5),
                'description' => $item['description'],
                'base_price' => $item['price'],
                'image' => $item['image_url'],
            ]);

            // 4. Create Product Variants
            // We'll create a combination of sizes and colors if both are provided.
            // If only one is provided, we create variants for that.
            // If neither, we create a single default variant.
            $sizes = $item['sizes'] ?? [];
            $colors = $item['colors'] ?? [];

            if (empty($sizes) && empty($colors)) {
                $this->createVariant($product, null, null, $item['image_url']);
            } elseif (empty($colors)) {
                foreach ($sizes as $size) {
                    $this->createVariant($product, $size, null, $item['image_url']);
                }
            } elseif (empty($sizes)) {
                foreach ($colors as $color) {
                    // Force the variant to use the parent image URL to ensure they are related/exact
                    $this->createVariant($product, null, $color['name'], $item['image_url']);
                }
            } else {
                foreach ($colors as $color) {
                    foreach ($sizes as $size) {
                        // Force the variant to use the parent image URL to ensure they are related/exact
                        $this->createVariant($product, $size, $color['name'], $item['image_url']);
                    }
                }
            }
        }
    }

    private function createVariant(Product $product, ?string $size, ?string $color, ?string $image): void
    {
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => strtoupper(Str::random(8)),
            'size' => $size,
            'color' => $color,
            'image' => $image,
            'price_adjustment' => 0.00,
            'purchase_price' => $product->base_price * 0.5,
            'stock_quantity' => rand(5, 20),
            'low_stock_threshold' => 5,
        ]);
    }
}
