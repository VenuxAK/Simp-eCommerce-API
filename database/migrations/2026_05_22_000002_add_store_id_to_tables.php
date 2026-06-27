<?php

use App\Modules\Store\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $nullableTables = ['users', 'customers'];
        $requiredTables = ['products', 'orders', 'categories', 'brands', 'discounts', 'suppliers', 'cash_sessions'];

        foreach (array_merge($nullableTables, $requiredTables) as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        $defaultStoreId = Store::first()->id ?? 1;

        foreach ($requiredTables as $table) {
            DB::table($table)->whereNull('store_id')->update(['store_id' => $defaultStoreId]);

            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('store_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        $allTables = ['users', 'customers', 'products', 'orders', 'categories', 'brands', 'discounts', 'suppliers', 'cash_sessions'];

        foreach ($allTables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('store_id')->nullable()->change();
            });
        }

        foreach ($allTables as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
        }
    }
};
