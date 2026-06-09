<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Declarative partitioning is specific to PostgreSQL
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        /*
         * 1. Audit Logs Partitioning
         * Audit logs grow indefinitely and are rarely queried across months.
         * Partitioning by month/year keeps indexes small and allows fast bulk deletion.
         */
        DB::statement('ALTER TABLE audit_logs RENAME TO audit_logs_old');

        // Create new partitioned table. The partition key (created_at) MUST be part of the Primary Key.
        DB::statement('
            CREATE TABLE audit_logs (
                id BIGSERIAL,
                user_id BIGINT,
                action VARCHAR(255) NOT NULL,
                model_type VARCHAR(255) NOT NULL,
                model_id BIGINT,
                old_values TEXT,
                new_values TEXT,
                ip_address VARCHAR(255),
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE,
                PRIMARY KEY (id, created_at)
            ) PARTITION BY RANGE (created_at)
        ');

        // Create partitions for 2026 and 2027
        DB::statement("CREATE TABLE audit_logs_2026 PARTITION OF audit_logs FOR VALUES FROM ('2026-01-01') TO ('2027-01-01')");
        DB::statement("CREATE TABLE audit_logs_2027 PARTITION OF audit_logs FOR VALUES FROM ('2027-01-01') TO ('2028-01-01')");

        // Copy existing data over
        DB::statement('INSERT INTO audit_logs SELECT * FROM audit_logs_old');

        /*
         * 2. Orders Partitioning (Documentation for Future Execution)
         *
         * Partitioning the orders table is more complex because of child tables 
         * (order_items, payments, invoices) referencing orders.id via Foreign Keys.
         * In PostgreSQL, partitioned tables can only be referenced by foreign keys 
         * if the FK includes all columns of the partition key.
         *
         * Steps required for the future Orders partitioning:
         * 1. Add order_created_at to order_items, payments, invoices, etc.
         * 2. Update their foreign keys to reference (order_id, order_created_at).
         * 3. Rename orders -> orders_old.
         * 4. Create orders PARTITION BY RANGE (created_at) with PK (id, created_at).
         * 5. Copy data over.
         *
         * DB::statement("ALTER TABLE orders RENAME TO orders_old");
         * DB::statement("CREATE TABLE orders (...) PARTITION BY RANGE (created_at)");
         * ...
         */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE audit_logs');
        DB::statement('ALTER TABLE audit_logs_old RENAME TO audit_logs');
    }
};
