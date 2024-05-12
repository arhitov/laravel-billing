<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

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
        Schema::table(config('billing.database.tables.fiscal_receipt'), static function (Blueprint $table) {
            $table->dropUnique('owner');
        });

        Schema::table(config('billing.database.tables.fiscal_receipt'), static function (Blueprint $table) {
            $table->index(['owner_type', 'owner_id'], 'owner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('billing.database.tables.fiscal_receipt'), static function (Blueprint $table) {
            $table->dropIndex('owner');
        });

        Schema::table(config('billing.database.tables.fiscal_receipt'), static function (Blueprint $table) {
            $table->unique(['owner_type', 'owner_id'], 'owner');
        });
    }
};
