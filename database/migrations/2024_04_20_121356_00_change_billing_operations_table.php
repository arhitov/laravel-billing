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
            $table->timestamp('state_waiting_for_capture_at')->nullable()->after('state_pending_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('billing.database.tables.operation'), static function (Blueprint $table) {
            $table->dropColumn(['state_waiting_for_capture_at']);
        });
    }
};
