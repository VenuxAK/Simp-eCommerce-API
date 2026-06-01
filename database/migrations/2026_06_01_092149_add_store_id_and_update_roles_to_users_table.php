<?php

use App\Modules\Identity\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete()->after('role');
        });

        // Migrate existing admin users to root role.
        User::where('role', 'admin')->update(['role' => 'root']);
    }

    public function down(): void
    {
        User::where('role', 'root')->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });
    }
};
