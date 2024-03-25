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
        Schema::create(config('billing.database.tables.saved_payment'), static function (Blueprint $table) {
            $table->id();
            $table->bigInteger('owner_balance_id')->unsigned()->index();
            $table->string('title', 50);
            $table->string('rebill_id');
            $table->string('gateway', 50);
            $table->string('card_first6', 6)->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('card_type', 50)->nullable();
            $table->timestamp('card_expiry_at')->nullable()->index();
            $table->string('issuer_country', 20)->nullable();
            $table->string('issuer_name')->nullable();
            $table->enum('state', ['created', 'active', 'inactive', 'insolvent', 'invalid', 'locked'])->index();
            $table->timestamp('active_at')->nullable()->index();
            $table->timestamp('inactive_at')->nullable()->index();
            $table->timestamp('insolvent_at')->nullable()->index();
            $table->timestamp('invalid_at')->nullable()->index();
            $table->timestamp('locked_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['rebill_id', 'gateway'], 'payment_method_gateway');

            $table->comment('Saved payment method');

            $table->foreign('owner_balance_id', 'fk_saved_payment_balance_id')
                  ->references('id')
                  ->on(config('billing.database.tables.balance'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('billing.database.tables.saved_payment'));
    }
};
