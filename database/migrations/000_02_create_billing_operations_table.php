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
        Schema::create(config('billing.database.tables.operation'), static function (Blueprint $table) {
            $table->id();
            $table->string('operation_identifier', 50)->nullable()->index()->comment('Operation identifier key');
            $table->uuid('operation_uuid')->unique();
            $table->bigInteger('linked_operation_id')->unsigned()->nullable()->index();
            $table->string('gateway', 50);
            $table->decimal('amount', 18, config('billing.rounding.precision'));
            $table->string('currency', 5);
            $table->bigInteger('sender_balance_id')->unsigned()->index();
            $table->decimal('sender_amount_before', 18, config('billing.rounding.precision'))->nullable();
            $table->decimal('sender_amount_after', 18, config('billing.rounding.precision'))->nullable();
            $table->bigInteger('recipient_balance_id')->unsigned()->index();
            $table->decimal('recipient_amount_before', 18, config('billing.rounding.precision'))->nullable();
            $table->decimal('recipient_amount_after', 18, config('billing.rounding.precision'))->nullable();
            $table->string('description', 1000)->nullable();
            $table->enum('state', ['created', 'pending', 'waiting_for_capture', 'succeeded', 'canceled', 'refund', 'errored'])->index();
            $table->timestamp('state_pending_at')->nullable()->index();
            $table->timestamp('state_succeeded_at')->nullable()->index();
            $table->timestamp('state_canceled_at')->nullable()->index();
            $table->timestamp('state_errored_at')->nullable()->index();
            $table->timestamp('state_refund_at')->nullable()->index();
            $table->timestamps();

            $table->comment('Balance transactions');

            $table->foreign('linked_operation_id', 'fk_operation_linked_id')
                  ->references('id')
                  ->on(config('billing.database.tables.operation'));

            $table->foreign('sender_balance_id', 'fk_operation_sender_balance_id')
                  ->references('id')
                  ->on(config('billing.database.tables.balance'));

            $table->foreign('recipient_balance_id', 'fk_operation_recipient_balance_id')
                  ->references('id')
                  ->on(config('billing.database.tables.balance'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('billing.database.tables.operation'));
    }
};
