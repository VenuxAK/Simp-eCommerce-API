<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMainStore();

        // Seed Spatie roles/permissions before creating any users
        // (UserFactory states assign Spatie roles via afterCreating).
        $this->call(RolePermissionSeeder::class);

        $this->seedUsers();
        $this->seedClothingStore();

        foreach (Store::all() as $store) {
            $this->seedStoreData($store);
        }

        $this->call(RolePermissionSeeder::class);
    }

    private function seedMainStore(): void
    {
        Store::firstOrCreate(
            ['slug' => 'main'],
            [
                'name' => 'Main Store',
                'description' => 'Primary store for SimpCommerce.',
                'is_active' => true,
                'settings' => json_encode(['currency' => 'MMK']),
            ]
        );
    }

    private function seedUsers(): void
    {
        if (! User::where('email', 'admin@simppos.test')->exists()) {
            User::factory()->root()->create([
                'name' => 'Admin',
                'email' => 'admin@simppos.test',
                'password' => bcrypt('Pass1234'),
            ]);
        }

        if (! User::where('email', 'staff@simppos.test')->exists()) {
            User::factory()->salesStaff()->create([
                'name' => 'Staff',
                'email' => 'staff@simppos.test',
                'password' => bcrypt('Pass1234'),
                'store_id' => Store::where('slug', 'main')->first()?->id,
            ]);
        }
    }

    private function seedClothingStore(): void
    {
        Store::firstOrCreate(
            ['slug' => 'clothing'],
            [
                'name' => 'Clothing Store',
                'description' => 'Clothing store for men\'s fashion.',
                'is_active' => true,
                'settings' => json_encode(['currency' => 'MMK', 'theme' => 'minimal']),
            ]
        );

        if (! User::where('email', 'clothing@simppos.test')->exists()) {
            $clothingStore = Store::where('slug', 'clothing')->first();

            User::factory()->storeOwner()->create([
                'name' => 'Clothing Admin',
                'email' => 'clothing@simppos.test',
                'password' => bcrypt('Pass1234'),
                'store_id' => $clothingStore->id,
            ]);
        }
    }

    private function seedStoreData(Store $store): void
    {
        $products = require database_path('data/products.php');

        // Skip if this store already has products seeded.
        if (Product::where('store_id', $store->id)->exists()) {
            return;
        }

        $brands = [
            'Adidas' => 'https://placehold.co/400x200?text=Adidas',
            'Nike' => 'https://placehold.co/400x200?text=Nike',
            'Puma' => 'https://placehold.co/400x200?text=Puma',
            'Zara' => 'https://placehold.co/400x200?text=Zara',
            'Vans' => 'https://placehold.co/400x200?text=Vans'
        ];
        $brandIds = [];
        foreach ($brands as $brandName => $brandLogo) {
            $brandIds[] = Brand::create([
                'name' => $brandName,
                'slug' => Str::slug($brandName).'-'.$store->slug,
                'logo' => $brandLogo,
                'store_id' => $store->id,
            ])->id;
        }

        $categoryIds = [];
        // Create main parent categories
        $mensClothingId = Category::create([
            'name' => 'Men\'s Clothing',
            'slug' => 'mens-clothing-'.$store->slug,
            'description' => 'Apparel for men',
            'store_id' => $store->id,
        ])->id;
        $womensClothingId = Category::create([
            'name' => 'Women\'s Clothing',
            'slug' => 'womens-clothing-'.$store->slug,
            'description' => 'Apparel for women',
            'store_id' => $store->id,
        ])->id;

        foreach ($products as $item) {
            $categoryName = $item['category'];

            if (! isset($categoryIds[$categoryName])) {
                // Randomly assign to men's or women's as parent
                $parentId = fake()->boolean() ? $mensClothingId : $womensClothingId;

                $category = Category::create([
                    'name' => $categoryName,
                    'slug' => Str::slug($categoryName).'-'.$store->slug,
                    'description' => fake()->sentence(),
                    'parent_id' => $parentId,
                    'store_id' => $store->id,
                ]);
                $categoryIds[$categoryName] = $category->id;
            }

            $product = Product::create([
                'name' => $item['name'],
                'slug' => Str::slug($item['name']).'-'.Str::random(6),
                'description' => $item['description'],
                'base_price' => $item['price'],
                'image' => $item['image_url'],
                'category_id' => $categoryIds[$categoryName],
                'brand_id' => fake()->randomElement($brandIds),
                'store_id' => $store->id,
            ]);

            $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $item['name']), 0, 4));

            foreach ($item['sizes'] as $size) {
                foreach ($item['colors'] as $color) {
                    $colorSlug = Str::slug($color['name']);
                    $sku = $product->id.'-'.$productCode.'-'.$colorSlug.'-'.$size;

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'size' => $size,
                        'color' => $color['name'],
                        'image' => $color['image'],
                        'price_adjustment' => 0,
                        'stock_quantity' => fake()->numberBetween(0, 50),
                    ]);
                }
            }
        }

        Customer::factory(10)->create([
            'store_id' => $store->id,
        ]);
    }
}
