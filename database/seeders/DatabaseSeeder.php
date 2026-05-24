<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Category;
use App\Modules\Customer\Models\Customer;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@simppos.test',
            'password' => bcrypt('Pass1234'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Staff',
            'email' => 'staff@simppos.test',
            'password' => bcrypt('Pass1234'),
            'role' => 'staff',
        ]);

        $categories = Category::factory(6)->create();

        $categories->each(function (Category $category) {
            Product::factory(3)
                ->has(ProductVariant::factory(4), 'variants')
                ->create(['category_id' => $category->id]);
        });

        Customer::factory(15)->create();
    }
}
