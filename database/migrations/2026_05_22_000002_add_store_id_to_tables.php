<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
        });
        Schema::table('discounts', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
        });
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', fn(Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
        Schema::table('categories', fn(Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
        Schema::table('orders', fn(Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
        Schema::table('discounts', fn(Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
        Schema::table('suppliers', fn(Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
        Schema::table('cash_sessions', fn(Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
    }
};
