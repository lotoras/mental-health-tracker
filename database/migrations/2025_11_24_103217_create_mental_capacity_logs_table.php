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
        Schema::create('mental_capacity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mental_state_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('capacity_before')->default(100); // Capacity % before this day
            $table->integer('capacity_after')->default(100);  // Capacity % after this day
            $table->integer('capacity_change')->default(0);   // The change from this day's state
            $table->boolean('triggered_breakdown')->default(false); // Did low capacity lead to breakdown?
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mental_capacity_logs');
    }
};
