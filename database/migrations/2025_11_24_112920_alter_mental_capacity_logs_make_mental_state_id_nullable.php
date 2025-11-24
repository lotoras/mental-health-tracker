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
        Schema::table('mental_capacity_logs', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['mental_state_id']);

            // Make the column nullable
            $table->foreignId('mental_state_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('mental_state_id')
                ->references('id')
                ->on('mental_states')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mental_capacity_logs', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['mental_state_id']);

            // Make the column not nullable again
            $table->foreignId('mental_state_id')->nullable(false)->change();

            // Re-add the foreign key constraint
            $table->foreign('mental_state_id')
                ->references('id')
                ->on('mental_states')
                ->onDelete('cascade');
        });
    }
};
