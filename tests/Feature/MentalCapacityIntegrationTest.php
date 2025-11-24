<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{User, MentalState, MentalCapacityLog};
use App\Services\MentalCapacityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentalCapacityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the complete flow: User creates entries and capacity is calculated correctly
     */
    public function test_complete_mental_capacity_flow(): void
    {
        $user = User::factory()->create();
        $service = new MentalCapacityService();

        // Simulate the example from requirements:
        // 1. First entry on 19.8.2025 with -10% (normaler_tag) should result in 90%
        $firstDate = Carbon::parse('2025-08-19');
        $firstState = MentalState::create([
            'user_id' => $user->id,
            'date' => $firstDate,
            'state_key' => 'normaler_tag', // -10%
        ]);

        // Recalculate capacity
        $service->recalculateCapacityFromFirstEntry($user);

        // Verify first entry
        $firstLog = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', $firstDate)
            ->first();

        $this->assertNotNull($firstLog);
        $this->assertEquals(100, $firstLog->capacity_before, 'First entry should start at 100%');
        $this->assertEquals(90, $firstLog->capacity_after, 'After -10% impact, should be 90%');

        // 2. Add second entry on 20.9.2025 with -20% (stressiger_tag)
        $secondDate = Carbon::parse('2025-09-20');
        $secondState = MentalState::create([
            'user_id' => $user->id,
            'date' => $secondDate,
            'state_key' => 'stressiger_tag', // -20%
        ]);

        // Recalculate capacity again
        $service->recalculateCapacityFromFirstEntry($user);

        // Verify the system calculated all days between first and second entry
        // There should be logs for every day from 19.8.2025 to 20.9.2025
        $logsCount = MentalCapacityLog::where('user_id', $user->id)
            ->whereBetween('date', [$firstDate, $secondDate])
            ->count();

        // From Aug 19 to Sep 20 = 33 days
        $expectedDays = $firstDate->diffInDays($secondDate) + 1;
        $this->assertEquals($expectedDays, $logsCount, 'Should have logs for all days between entries');

        // Verify first entry is still correct
        $firstLog = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', $firstDate)
            ->first();
        $this->assertEquals(90, $firstLog->capacity_after);

        // Check an empty day (e.g., 2025-08-20)
        $emptyDay = Carbon::parse('2025-08-20');
        $emptyLog = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', $emptyDay)
            ->first();
        $this->assertEquals(90, $emptyLog->capacity_before, 'Empty day should start from previous day');
        $this->assertEquals(90, $emptyLog->capacity_after, 'Empty day should maintain capacity (0% drain)');
        $this->assertNull($emptyLog->mental_state_id, 'Empty day should not have a mental state');

        // Verify second entry starts from 90% (due to all empty days maintaining capacity)
        $secondLog = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', $secondDate)
            ->first();
        $this->assertEquals(90, $secondLog->capacity_before, 'Empty days maintain capacity at 90%');
        $this->assertEquals(70, $secondLog->capacity_after, '90% - 20% = 70%');
    }

    /**
     * Test that adding entries out of order works correctly
     */
    public function test_out_of_order_entries_are_handled(): void
    {
        $user = User::factory()->create();
        $service = new MentalCapacityService();

        // Add entries in reverse order
        MentalState::create([
            'user_id' => $user->id,
            'date' => Carbon::parse('2025-08-22'),
            'state_key' => 'normaler_tag',
        ]);

        MentalState::create([
            'user_id' => $user->id,
            'date' => Carbon::parse('2025-08-19'),
            'state_key' => 'ruhetag',
        ]);

        MentalState::create([
            'user_id' => $user->id,
            'date' => Carbon::parse('2025-08-21'),
            'state_key' => 'entspannter_tag',
        ]);

        // Recalculate should process them in chronological order
        $service->recalculateCapacityFromFirstEntry($user);

        // Verify they were processed in chronological order
        $log1 = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', '2025-08-19')
            ->first();
        $this->assertEquals(100, $log1->capacity_before, 'First chronological entry should start at 100%');

        $log2 = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', '2025-08-20')
            ->first();
        $this->assertEquals(100, $log2->capacity_after, 'Empty day should maintain at 100%');

        $log3 = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', '2025-08-21')
            ->first();
        $this->assertEquals(100, $log3->capacity_before, 'Should continue from empty day at 100%');
    }

    /**
     * Test realistic scenario with various mental states
     */
    public function test_realistic_weekly_scenario(): void
    {
        $user = User::factory()->create();
        $service = new MentalCapacityService();

        // Simulate a week of entries
        $startDate = Carbon::parse('2025-08-19');
        $entries = [
            0 => 'ruhetag',           // Tuesday: +30% = 100% (start 100%, capped)
            1 => 'entspannter_tag',   // Wednesday: 0% = 100%
            2 => 'normaler_tag',      // Thursday: -10% = 90%
            3 => 'stressiger_tag',    // Friday: -20% = 70%
            // Saturday: no entry (empty) = 0%
            5 => 'halb_ruhetag',      // Sunday: +15% = 15%
            6 => 'ruhetag',           // Monday: +30% = 45%
        ];

        foreach ($entries as $dayOffset => $stateKey) {
            MentalState::create([
                'user_id' => $user->id,
                'date' => $startDate->copy()->addDays($dayOffset),
                'state_key' => $stateKey,
            ]);
        }

        $service->recalculateCapacityFromFirstEntry($user);

        // Verify the progression
        $expectations = [
            0 => ['before' => 100, 'after' => 100],  // 100% + 30% = 100% (capped)
            1 => ['before' => 100, 'after' => 100],  // 100% + 0% = 100%
            2 => ['before' => 100, 'after' => 90],   // 100% - 10% = 90%
            3 => ['before' => 90, 'after' => 70],    // 90% - 20% = 70%
            4 => ['before' => 70, 'after' => 70],    // Empty day maintains at 70%
            5 => ['before' => 70, 'after' => 85],    // 70% + 15% = 85%
            6 => ['before' => 85, 'after' => 100],   // 85% + 30% = 115% (capped to 100%)
        ];

        foreach ($expectations as $dayOffset => $expected) {
            $date = $startDate->copy()->addDays($dayOffset);
            $log = MentalCapacityLog::where('user_id', $user->id)
                ->where('date', $date->format('Y-m-d'))
                ->first();

            $this->assertNotNull($log, "Log should exist for day {$dayOffset}");
            $this->assertEquals($expected['before'], $log->capacity_before, "Day {$dayOffset} capacity_before mismatch");
            $this->assertEquals($expected['after'], $log->capacity_after, "Day {$dayOffset} capacity_after mismatch");
        }

        // Verify current capacity (considering days up to today)
        $currentCapacity = $service->getCurrentCapacity($user);

        // Since entries are in the past (August 2025), and today is November 2025,
        // all days after the last entry maintain the capacity at 100%
        $this->assertEquals(100, $currentCapacity, 'Capacity should be 100% maintained through empty days until today');
    }

    /**
     * Test breakdown detection
     */
    public function test_breakdown_detection(): void
    {
        $user = User::factory()->create();
        $service = new MentalCapacityService();

        // Create conditions that lead to a breakdown
        $startDate = Carbon::parse('2025-08-19');

        // Multiple stressful days
        for ($i = 0; $i < 3; $i++) {
            MentalState::create([
                'user_id' => $user->id,
                'date' => $startDate->copy()->addDays($i),
                'state_key' => 'stressiger_tag', // -20% each
            ]);
        }

        // After 3 stressful days: 100% - 20% - 20% - 20% = 40%
        // Then add one very stressful day: 40% - 30% = 10%
        MentalState::create([
            'user_id' => $user->id,
            'date' => $startDate->copy()->addDays(3),
            'state_key' => 'sehr_stressiger_tag', // -30%
        ]);

        // Then a breakdown
        MentalState::create([
            'user_id' => $user->id,
            'date' => $startDate->copy()->addDays(4),
            'state_key' => 'im_loch', // breakdown
        ]);

        $service->recalculateCapacityFromFirstEntry($user);

        // Check capacity before breakdown
        $log4 = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', $startDate->copy()->addDays(3))
            ->first();
        $this->assertEquals(10, $log4->capacity_after, '4th day should have 10% capacity (below threshold)');

        // Check breakdown was logged
        $breakdownLog = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', $startDate->copy()->addDays(4))
            ->first();
        $this->assertNotNull($breakdownLog);
        $this->assertTrue($breakdownLog->triggered_breakdown, 'Breakdown should be flagged as triggered by low capacity');
    }
}
