<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mental_state_types', function (Blueprint $table) {
            $table->integer('capacity_impact')->default(0)->after('severity'); // Positive = fills up, Negative = drains
            $table->boolean('is_breakdown')->default(false)->after('capacity_impact'); // Track breakdown states
        });

        // Update the capacity impact values for each state
        DB::table('mental_state_types')->where('key', 'im_loch')->update([
            'capacity_impact' => 40, // Fills up a lot (but bad way)
            'is_breakdown' => true,
        ]);

        DB::table('mental_state_types')->where('key', 'halb_im_loch')->update([
            'capacity_impact' => 25, // Fills up (but bad way)
            'is_breakdown' => true,
        ]);

        DB::table('mental_state_types')->where('key', 'sehr_stressiger_tag')->update([
            'capacity_impact' => -30, // Takes a lot
            'is_breakdown' => false,
        ]);

        DB::table('mental_state_types')->where('key', 'stressiger_tag')->update([
            'capacity_impact' => -20, // Takes
            'is_breakdown' => false,
        ]);

        DB::table('mental_state_types')->where('key', 'normaler_tag')->update([
            'capacity_impact' => -10, // Takes a bit
            'is_breakdown' => false,
        ]);

        DB::table('mental_state_types')->where('key', 'entspannter_tag')->update([
            'capacity_impact' => 0, // Doesn't take
            'is_breakdown' => false,
        ]);

        DB::table('mental_state_types')->where('key', 'halb_ruhetag')->update([
            'capacity_impact' => 15, // Fills up a bit
            'is_breakdown' => false,
        ]);

        DB::table('mental_state_types')->where('key', 'ruhetag')->update([
            'capacity_impact' => 30, // Fills up a lot
            'is_breakdown' => false,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mental_state_types', function (Blueprint $table) {
            $table->dropColumn(['capacity_impact', 'is_breakdown']);
        });
    }
};
