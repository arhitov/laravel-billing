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
        Schema::create(config('billing.database.tables.fiscal_receipt'), static function (Blueprint $table) {
            $table->id();
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->uuid('operation_uuid')->unique();
            $table->string('gateway', 50)->index();
            $table->decimal('amount', 18, config('billing.rounding.precision'));
            $table->string('currency', 5);
            $table->json('receipt_data');
            $table->enum('state', ['created', 'pending', 'paid', 'send', 'succeeded', 'canceled'])->index();
            $table->timestamp('state_pending_at')->nullable()->index();
            $table->timestamp('state_paid_at')->nullable()->index();
            $table->timestamp('state_send_at')->nullable()->index();
            $table->timestamp('state_succeeded_at')->nullable()->index();
            $table->timestamp('state_canceled_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id'], 'owner');

            $table->comment('Owner balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('billing.database.tables.fiscal_receipt'));
    }
};
