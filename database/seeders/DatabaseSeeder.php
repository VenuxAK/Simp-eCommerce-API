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
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@simppos.test',
                'password' => bcrypt('Pass1234'),
            ]);
            $admin->assignRole('root');
        }

        if (! User::where('email', 'staff@simppos.test')->exists()) {
            $staff = User::create([
                'name' => 'Staff',
                'email' => 'staff@simppos.test',
                'password' => bcrypt('Pass1234'),
                'store_id' => Store::where('slug', 'main')->first()?->id,
            ]);
            $staff->assignRole('sales_staff');
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

            $owner = User::create([
                'name' => 'Clothing Admin',
                'email' => 'clothing@simppos.test',
                'password' => bcrypt('Pass1234'),
                'store_id' => $clothingStore->id,
            ]);
            $owner->assignRole('store_owner');
        }
    }

    private function seedStoreData(Store $store): void
    {
        if (! Customer::where('email', 'customer@simppos.test')->where('store_id', $store->id)->exists()) {
            Customer::create([
                'name' => 'Demo Customer',
                'email' => 'customer@simppos.test',
                'password' => bcrypt('Pass1234'),
                'store_id' => $store->id,
            ]);
        }

        (new StoreCatalogSeeder)->run($store);
    }
}
