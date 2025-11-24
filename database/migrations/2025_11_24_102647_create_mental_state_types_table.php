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
        Schema::create('mental_state_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('color')->default('#6b7280'); // Default gray
            $table->integer('severity')->default(0); // 0-10 scale, 10 being worst
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Insert the custom German state types
        DB::table('mental_state_types')->insert([
            [
                'key' => 'im_loch',
                'label' => 'Im Loch',
                'color' => '#7c2d12', // Very dark red/brown
                'severity' => 10,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'halb_im_loch',
                'label' => 'Halb im Loch',
                'color' => '#b91c1c', // Dark red
                'severity' => 8,
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sehr_stressiger_tag',
                'label' => 'Sehr stressiger Tag',
                'color' => '#dc2626', // Red
                'severity' => 7,
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'stressiger_tag',
                'label' => 'Stressiger Tag',
                'color' => '#f97316', // Orange
                'severity' => 6,
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'normaler_tag',
                'label' => 'Normaler Tag',
                'color' => '#fbbf24', // Yellow/amber
                'severity' => 5,
                'order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'entspannter_tag',
                'label' => 'Entspannter Tag',
                'color' => '#4ade80', // Light green
                'severity' => 3,
                'order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'halb_ruhetag',
                'label' => 'Halb Ruhetag',
                'color' => '#22c55e', // Green
                'severity' => 2,
                'order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ruhetag',
                'label' => 'Ruhetag',
                'color' => '#059669', // Dark green/emerald
                'severity' => 1,
                'order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mental_state_types');
    }
};
