<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('method');
            $table->string('transaction_id')->nullable()->after('gateway');
            $table->string('gateway_status')->nullable()->after('transaction_id');
            $table->json('gateway_response')->nullable()->after('gateway_status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'transaction_id', 'gateway_status', 'gateway_response']);
        });
    }
};
