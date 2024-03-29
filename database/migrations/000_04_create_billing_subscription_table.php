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
        Schema::create(config('billing.database.tables.subscription'), static function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->string('key')->index()->comment('Unique subscription name on your system.');
            $table->string('key_extend')->nullable()->comment('More information about subscriptions');
            $table->unsignedBigInteger('balance_id')->nullable()->index()->comment('The balance from which the payment was made.');
            $table->string('currency', 5)->nullable();
            $table->decimal('amount', 18, config('billing.rounding.precision'))->nullable()->comment('The amount that was paid upon purchase.');
            $table->timestamp('beginning_at')->nullable()->index()->comment('Subscription start date.');
            $table->timestamp('expiry_at')->nullable()->index()->comment('Subscription expiration date.');
            $table->enum('state', ['pending', 'active', 'inactive', 'expiry'])->index();
            $table->timestamp('state_pending_at')->nullable()->index();
            $table->timestamp('state_active_at')->nullable()->index();
            $table->timestamp('state_inactive_at')->nullable()->index();
            $table->timestamp('state_locked_at')->nullable()->index();
            $table->timestamp('state_expiry_at')->nullable()->index();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
            $table->unique(['owner_type', 'owner_id', 'key']);

            $table->comment('Owner subscriptions.');

            $table->foreign('balance_id', 'fk_subscription_balance_id')
                ->references('id')
                ->on(config('billing.database.tables.balance'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('billing.database.tables.subscription'));
    }
};
