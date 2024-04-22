<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(config('billing.database.tables.operation'), static function (Blueprint $table) {
            $table->string('gateway_payment_id', 100)->nullable()->after('gateway');
            $table->string('gateway_payment_state', 100)->nullable()->after('gateway_payment_id');

            $table->unique(['gateway', 'gateway_payment_id']);
            $table->index(['gateway', 'gateway_payment_state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('billing.database.tables.operation'), static function (Blueprint $table) {
            $table->dropUnique(['gateway', 'gateway_payment_id']);
            $table->dropIndex(['gateway', 'gateway_payment_state']);

            $table->dropColumn(['gateway_payment_id', 'gateway_payment_state']);
        });
    }
};
