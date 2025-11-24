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
        Schema::table('mental_states', function (Blueprint $table) {
            // Drop the old enum column
            $table->dropColumn('state');
        });

        Schema::table('mental_states', function (Blueprint $table) {
            // Add new column that references the state types table key
            $table->string('state_key')->after('date');
            $table->foreign('state_key')
                  ->references('key')
                  ->on('mental_state_types')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mental_states', function (Blueprint $table) {
            $table->dropForeign(['state_key']);
            $table->dropColumn('state_key');
        });

        Schema::table('mental_states', function (Blueprint $table) {
            $table->enum('state', ['excellent', 'good', 'okay', 'bad', 'worst'])->after('date');
        });
    }
};
