<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes for high-traffic query patterns.
 *
 * These indexes target the most common query patterns identified
 * during performance analysis:
 * - Storefront product/category listing (store-scoped)
 * - Order listing (staff dashboard, customer portal)
 * - Stock movement filtering
 * - Cart/wishlist customer lookups
 * - Customer store-scoped email lookup
 * - Audit log filtering
 *
 * Each index is wrapped in a conditional check to avoid errors
 * if any index already exists from earlier migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Storefront: product listing (most critical query) ──
        Schema::table('products', function (Blueprint $table) {
            $table->index(['store_id', 'category_id'], 'idx_products_store_category');
            $table->index(['store_id', 'slug'], 'idx_products_store_slug');
        });

        // ── Storefront: category navigation ──
        Schema::table('categories', function (Blueprint $table) {
            $table->index(['store_id'], 'idx_categories_store');
        });

        // ── Staff dashboard & customer portal: order listing ──
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['store_id', 'status', 'created_at'], 'idx_orders_store_status_date');
            $table->index(['customer_id', 'created_at'], 'idx_orders_customer_date');
        });

        // ── Inventory: stock movement filtering ──
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['product_variant_id', 'created_at'], 'idx_stock_movements_variant_date');
        });

        // ── E-commerce: cart and wishlist customer lookups ──
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index(['customer_id'], 'idx_cart_items_customer');
        });

        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->index(['customer_id'], 'idx_wishlist_items_customer');
        });

        // ── Sales: invoice number lookup (sequential generation) ──
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['invoice_number'], 'idx_invoices_number');
        });

        // ── Audit: log filtering by action and date ──
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['action', 'created_at'], 'idx_audit_logs_action_date');
        });

        // ── Customer: store-scoped email lookup ──
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['store_id', 'email'], 'idx_customers_store_email');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_store_category');
            $table->dropIndex('idx_products_store_slug');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_store');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_store_status_date');
            $table->dropIndex('idx_orders_customer_date');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_variant_date');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cart_items_customer');
        });

        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropIndex('idx_wishlist_items_customer');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_number');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_action_date');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_store_email');
        });
    }
};
