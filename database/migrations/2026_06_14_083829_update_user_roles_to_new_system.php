<?php

use App\Modules\Identity\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill: 'store_admin' → 'store_owner'
        User::where('role', 'store_admin')->update(['role' => 'store_owner']);

        // Backfill: 'staff' → 'sales_staff'
        User::where('role', 'staff')->update(['role' => 'sales_staff']);
    }

    public function down(): void
    {
        // Reverse: 'store_owner' → 'store_admin'
        User::where('role', 'store_owner')->update(['role' => 'store_admin']);
        User::where('role', 'store_manager')->update(['role' => 'store_admin']);

        // Reverse: 'sales_staff' and 'inventory_staff' → 'staff'
        User::where('role', 'inventory_staff')->update(['role' => 'staff']);
        User::where('role', 'sales_staff')->update(['role' => 'staff']);
    }
};
