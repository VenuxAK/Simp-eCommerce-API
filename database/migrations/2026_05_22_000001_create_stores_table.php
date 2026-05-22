<?php

use App\Modules\Store\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Create default store for existing single-store setup.
        Store::create([
            'name' => 'Main Store',
            'slug' => 'main',
            'description' => 'Default store for single-store operation.',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
