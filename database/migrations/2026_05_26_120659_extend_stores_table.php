<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('slug');
            $table->string('logo')->nullable()->after('description');
            $table->string('phone')->nullable()->after('logo');
            $table->string('email')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['domain', 'logo', 'phone', 'email']);
        });
    }
};
