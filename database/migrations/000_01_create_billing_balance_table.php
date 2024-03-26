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
        Schema::create(config('billing.database.tables.balance'), static function (Blueprint $table) {
            $table->id();
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->string('key')->index();
            $table->decimal('amount', 18, config('billing.rounding.precision'));
            $table->string('currency', 5);
            $table->decimal('limit', 18, config('billing.rounding.precision'))->unsigned()->nullable()->default(0);
            $table->enum('state', ['active', 'inactive', 'locked'])->index();
            $table->timestamp('state_active_at')->nullable()->index();
            $table->timestamp('state_inactive_at')->nullable()->index();
            $table->timestamp('state_locked_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id']);
            $table->unique(['owner_type', 'owner_id', 'key']);

            $table->comment('Owner balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('billing.database.tables.balance'));
    }
};
