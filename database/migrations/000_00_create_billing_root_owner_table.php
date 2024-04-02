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
        if (config('billing.root_owner.table')) {
            Schema::create(config('billing.root_owner.table'), static function(Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();

                $table->comment('Root owner.');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('billing.root_owner')) {
            Schema::dropIfExists(config('billing.root_owner.table'));
        }
    }
};
