<?php

use App\Modules\Store\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add store_id to customers table for store-level scoping.
 *
 * Customers registered through a specific storefront will be tagged
 * with that store's ID. Existing customers get the default store.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete()->after('id');
        });

        // Backfill existing customers to the main store.
        $defaultStoreId = Store::first()->id ?? 1;
        DB::table('customers')->whereNull('store_id')->update(['store_id' => $defaultStoreId]);

        // Make NOT NULL now that all rows have a store_id.
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });
    }
};
