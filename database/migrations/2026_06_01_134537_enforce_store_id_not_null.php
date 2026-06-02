<?php

use App\Modules\Store\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enforce store_id NOT NULL on all scoped tables.
 *
 * First backfills any null store_id rows to the main store,
 * then adds the NOT NULL constraint as a safety net.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Resolve the default store for backfilling.
        $defaultStoreId = Store::first()->id ?? 1;

        $tables = ['products', 'orders', 'categories', 'discounts', 'suppliers', 'cash_sessions'];

        foreach ($tables as $table) {
            // Backfill any rows that slipped through with null store_id.
            DB::table($table)->whereNull('store_id')->update(['store_id' => $defaultStoreId]);

            // Add NOT NULL constraint.
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('store_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        $tables = ['products', 'orders', 'categories', 'discounts', 'suppliers', 'cash_sessions'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('store_id')->nullable()->change();
            });
        }
    }
};
