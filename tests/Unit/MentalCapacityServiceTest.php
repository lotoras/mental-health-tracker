<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\{User, MentalState, MentalStateType, MentalCapacityLog};
use App\Services\MentalCapacityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentalCapacityServiceTest extends TestCase
{
    use RefreshDatabase;

    private MentalCapacityService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MentalCapacityService();
        $this->user = User::factory()->create();
    }

    /**
     * Test 1: First entry should start at 100% and apply impact
     * Example: 19.8.2025 with -10% (normaler_tag) should result in 90%
     */
    public function test_first_entry_starts_at_100_percent_and_applies_impact(): void
    {
        // Create first entry on 2025-08-19 with "normaler_tag" (-10%)
        $firstDate = Carbon::parse('2025-08-19');
        $mentalState = MentalState::create([
            'user_id' => $this->user->id,
            'date' => $firstDate,
            'state_key' => 'normaler_tag',
        ]);

        // Recalculate from first entry
        $this->service->recalculateCapacityFromFirstEntry($this->user);

        // Check the capacity log for this date
        $log = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', $firstDate)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(100, $log->capacity_before, 'First entry should start at 100%');
        $this->assertEquals(90, $log->capacity_after, 'After -10% impact, capacity should be 90%');
        $this->assertEquals(-10, $log->capacity_change);
    }

    /**
     * Test 2: Second entry should build on first entry's result
     * Example: 19.8.2025 (-10%) = 90%, then 20.9.2025 (-20%) should result in 70%
     */
    public function test_second_entry_builds_on_first_entry(): void
    {
        // First entry: 2025-08-19 with "normaler_tag" (-10%)
        $firstDate = Carbon::parse('2025-08-19');
        MentalState::create([
            'user_id' => $this->user->id,
            'date' => $firstDate,
            'state_key' => 'normaler_tag',
        ]);

        // Second entry: 2025-09-20 with "stressiger_tag" (-20%)
        $secondDate = Carbon::parse('2025-09-20');
        MentalState::create([
            'user_id' => $this->user->id,
            'date' => $secondDate,
            'state_key' => 'stressiger_tag',
        ]);

        // Recalculate from first entry
        $this->service->recalculateCapacityFromFirstEntry($this->user);

        // Check first entry
        $firstLog = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', $firstDate)
            ->first();
        $this->assertEquals(100, $firstLog->capacity_before);
        $this->assertEquals(90, $firstLog->capacity_after);

        // Check second entry - should start from first entry's result (empty days maintain capacity)
        $secondLog = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', $secondDate)
            ->first();
        $this->assertEquals(90, $secondLog->capacity_before, 'Empty dates maintain capacity at 90%');
        $this->assertEquals(70, $secondLog->capacity_after, '90% - 20% = 70%');
    }

    /**
     * Test 3: Empty dates (no entries) should maintain capacity (0% drain)
     * Example: 19.8.2025 (-10%) = 90%, then 21.8.2025 should still have 90% on 20.8.2025
     */
    public function test_empty_dates_maintain_capacity(): void
    {
        // Entry on 2025-08-19
        $firstDate = Carbon::parse('2025-08-19');
        MentalState::create([
            'user_id' => $this->user->id,
            'date' => $firstDate,
            'state_key' => 'normaler_tag', // -10%
        ]);

        // Entry on 2025-08-21 (skipping 2025-08-20)
        $thirdDate = Carbon::parse('2025-08-21');
        MentalState::create([
            'user_id' => $this->user->id,
            'date' => $thirdDate,
            'state_key' => 'entspannter_tag', // 0%
        ]);

        // Recalculate
        $this->service->recalculateCapacityFromFirstEntry($this->user);

        // Check 2025-08-19: 100% -> 90%
        $log1 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', $firstDate)
            ->first();
        $this->assertEquals(100, $log1->capacity_before);
        $this->assertEquals(90, $log1->capacity_after);

        // Check 2025-08-20 (empty day): should maintain capacity at 90%
        $emptyDate = Carbon::parse('2025-08-20');
        $logEmpty = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', $emptyDate)
            ->first();
        $this->assertNotNull($logEmpty, 'Empty date should have a log entry');
        $this->assertEquals(90, $logEmpty->capacity_before, 'Empty date should start from previous day');
        $this->assertEquals(90, $logEmpty->capacity_after, 'Empty date should maintain capacity (0% drain)');
        $this->assertEquals(0, $logEmpty->capacity_change);
        $this->assertNull($logEmpty->mental_state_id, 'Empty date should not have a mental state');

        // Check 2025-08-21: starts from 90% (because previous day maintained capacity)
        $log3 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', $thirdDate)
            ->first();
        $this->assertEquals(90, $log3->capacity_before, 'Should start from 90% after empty day');
        $this->assertEquals(90, $log3->capacity_after, '90% + 0% impact = 90%');
    }

    /**
     * Test 4: Multiple empty dates should all maintain capacity
     */
    public function test_multiple_empty_dates_maintain_capacity(): void
    {
        // Entry on 2025-08-19
        $firstDate = Carbon::parse('2025-08-19');
        MentalState::create([
            'user_id' => $this->user->id,
            'date' => $firstDate,
            'state_key' => 'ruhetag', // +30%
        ]);

        // Entry on 2025-08-24 (skipping 20, 21, 22, 23)
        $fifthDate = Carbon::parse('2025-08-24');
        MentalState::create([
            'user_id' => $this->user->id,
            'date' => $fifthDate,
            'state_key' => 'normaler_tag', // -10%
        ]);

        // Recalculate
        $this->service->recalculateCapacityFromFirstEntry($this->user);

        // Check first day: 100% + 30% = 100% (capped)
        $log1 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', $firstDate)
            ->first();
        $this->assertEquals(100, $log1->capacity_before);
        $this->assertEquals(100, $log1->capacity_after, 'Capacity should be capped at 100%');

        // Check empty days (20, 21, 22, 23) - all should maintain 100% capacity
        for ($day = 20; $day <= 23; $day++) {
            $emptyDate = Carbon::parse("2025-08-{$day}");
            $logEmpty = MentalCapacityLog::where('user_id', $this->user->id)
                ->where('date', $emptyDate)
                ->first();
            $this->assertEquals(100, $logEmpty->capacity_before, "Day {$day} should start at 100%");
            $this->assertEquals(100, $logEmpty->capacity_after, "Day {$day} should maintain at 100%");
            $this->assertEquals(0, $logEmpty->capacity_change, "Day {$day} should have 0% change");
        }

        // Check 2025-08-24: starts from 100%
        $log5 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', $fifthDate)
            ->first();
        $this->assertEquals(100, $log5->capacity_before, 'Should start at 100% after empty days');
        $this->assertEquals(90, $log5->capacity_after, '100% - 10% = 90%');
    }

    /**
     * Test 5: Capacity should be capped at 100%
     */
    public function test_capacity_capped_at_100_percent(): void
    {
        // Multiple rest days in a row
        $dates = [
            '2025-08-19' => 'ruhetag',      // +30%
            '2025-08-20' => 'ruhetag',      // +30%
            '2025-08-21' => 'halb_ruhetag', // +15%
        ];

        foreach ($dates as $date => $stateKey) {
            MentalState::create([
                'user_id' => $this->user->id,
                'date' => Carbon::parse($date),
                'state_key' => $stateKey,
            ]);
        }

        $this->service->recalculateCapacityFromFirstEntry($this->user);

        // Day 1: 100% (start) + 30% = 100% (capped)
        $log1 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', '2025-08-19')
            ->first();
        $this->assertEquals(100, $log1->capacity_after);

        // Day 2: 100% + 30% = 100% (capped)
        $log2 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', '2025-08-20')
            ->first();
        $this->assertEquals(100, $log2->capacity_after);

        // Day 3: 100% + 15% = 100% (capped)
        $log3 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', '2025-08-21')
            ->first();
        $this->assertEquals(100, $log3->capacity_after);
    }

    /**
     * Test 6: Capacity should be clamped at 0% (cannot go negative)
     */
    public function test_capacity_cannot_go_negative(): void
    {
        // Start with low capacity
        $dates = [
            '2025-08-19' => 'normaler_tag',        // -10% -> 90%
            '2025-08-20' => 'sehr_stressiger_tag', // -30% -> 60%
            '2025-08-21' => 'sehr_stressiger_tag', // -30% -> 30%
            '2025-08-22' => 'sehr_stressiger_tag', // -30% -> 0% (clamped)
        ];

        foreach ($dates as $date => $stateKey) {
            MentalState::create([
                'user_id' => $this->user->id,
                'date' => Carbon::parse($date),
                'state_key' => $stateKey,
            ]);
        }

        $this->service->recalculateCapacityFromFirstEntry($this->user);

        $log4 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', '2025-08-22')
            ->first();
        $this->assertEquals(30, $log4->capacity_before);
        $this->assertEquals(0, $log4->capacity_after, 'Capacity should be clamped at 0%');
        $this->assertEquals(-30, $log4->capacity_change);
    }

    /**
     * Test 7: Complex scenario with mix of entries and empty dates
     */
    public function test_complex_scenario_with_mixed_entries(): void
    {
        $entries = [
            '2025-08-19' => 'normaler_tag',        // Start: 100%, -10% = 90%
            // 2025-08-20: empty -> 0%
            '2025-08-21' => 'halb_ruhetag',        // Start: 0%, +15% = 15%
            '2025-08-22' => 'ruhetag',             // Start: 15%, +30% = 45%
            // 2025-08-23: empty -> 0%
            // 2025-08-24: empty -> 0%
            '2025-08-25' => 'stressiger_tag',      // Start: 0%, -20% = 0% (clamped)
        ];

        foreach ($entries as $date => $stateKey) {
            MentalState::create([
                'user_id' => $this->user->id,
                'date' => Carbon::parse($date),
                'state_key' => $stateKey,
            ]);
        }

        $this->service->recalculateCapacityFromFirstEntry($this->user);

        // Verify each day
        $expectations = [
            '2025-08-19' => ['before' => 100, 'after' => 90],
            '2025-08-20' => ['before' => 90, 'after' => 90],   // empty - maintain
            '2025-08-21' => ['before' => 90, 'after' => 100],  // 90% + 15% = 105% (clamped to 100%)
            '2025-08-22' => ['before' => 100, 'after' => 100], // 100% + 30% = 130% (clamped to 100%)
            '2025-08-23' => ['before' => 100, 'after' => 100], // empty - maintain
            '2025-08-24' => ['before' => 100, 'after' => 100], // empty - maintain
            '2025-08-25' => ['before' => 100, 'after' => 80],  // 100% - 20% = 80%
        ];

        foreach ($expectations as $date => $expected) {
            $log = MentalCapacityLog::where('user_id', $this->user->id)
                ->where('date', $date)
                ->first();
            $this->assertNotNull($log, "Log should exist for {$date}");
            $this->assertEquals($expected['before'], $log->capacity_before, "{$date} capacity_before mismatch");
            $this->assertEquals($expected['after'], $log->capacity_after, "{$date} capacity_after mismatch");
        }
    }

    /**
     * Test 8: getCurrentCapacity returns correct current capacity
     */
    public function test_get_current_capacity(): void
    {
        // Create entries up to today
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        MentalState::create([
            'user_id' => $this->user->id,
            'date' => $yesterday,
            'state_key' => 'ruhetag', // +30%
        ]);

        MentalState::create([
            'user_id' => $this->user->id,
            'date' => $today,
            'state_key' => 'normaler_tag', // -10%
        ]);

        $this->service->recalculateCapacityFromFirstEntry($this->user);

        $currentCapacity = $this->service->getCurrentCapacity($this->user);
        $this->assertEquals(90, $currentCapacity, '100% + 30% (capped at 100%) then - 10% = 90%');
    }

    /**
     * Test 9: User with no entries should have 100% capacity
     */
    public function test_user_with_no_entries_has_100_percent_capacity(): void
    {
        $currentCapacity = $this->service->getCurrentCapacity($this->user);
        $this->assertEquals(100, $currentCapacity);
    }

    /**
     * Test 10: Breakdown states should be tracked
     */
    public function test_breakdown_states_are_tracked(): void
    {
        MentalState::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-08-19'),
            'state_key' => 'im_loch', // +40%, is_breakdown
        ]);

        $this->service->recalculateCapacityFromFirstEntry($this->user);

        $log = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', '2025-08-19')
            ->first();

        // im_loch has is_breakdown = true
        $this->assertFalse($log->triggered_breakdown, 'First breakdown at 100% capacity should not be triggered by low capacity');
    }

    /**
     * Test 11: Recalculation should handle out-of-order entry additions
     */
    public function test_recalculation_handles_out_of_order_entries(): void
    {
        // Add entries out of chronological order
        MentalState::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-08-22'),
            'state_key' => 'normaler_tag',
        ]);

        MentalState::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-08-19'),
            'state_key' => 'ruhetag',
        ]);

        MentalState::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-08-21'),
            'state_key' => 'entspannter_tag',
        ]);

        // Recalculate should process them in chronological order
        $this->service->recalculateCapacityFromFirstEntry($this->user);

        // Verify chronological processing
        $log1 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', '2025-08-19')
            ->first();
        $this->assertEquals(100, $log1->capacity_before, 'First chronological entry should start at 100%');
        $this->assertEquals(100, $log1->capacity_after);

        $log2 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', '2025-08-20')
            ->first();
        $this->assertEquals(100, $log2->capacity_after, 'Empty day should maintain at 100%');

        $log3 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', '2025-08-21')
            ->first();
        $this->assertEquals(100, $log3->capacity_before, 'Should continue from previous day at 100%');
    }

    /**
     * Test 12: The specific example from requirements
     * 19.8.2025 -10% = 90%, then 20.9.2025 -20% = 70%
     */
    public function test_specific_requirement_example(): void
    {
        // First entry: 19.8.2025 with -10%
        MentalState::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-08-19'),
            'state_key' => 'normaler_tag', // -10%
        ]);

        // Second entry: 20.9.2025 with -20%
        MentalState::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-09-20'),
            'state_key' => 'stressiger_tag', // -20%
        ]);

        $this->service->recalculateCapacityFromFirstEntry($this->user);

        // First entry should be: 100% - 10% = 90%
        $log1 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', '2025-08-19')
            ->first();
        $this->assertEquals(100, $log1->capacity_before);
        $this->assertEquals(90, $log1->capacity_after);

        // All days between should maintain capacity at 90%
        // The second entry (20.9.2025) should start from 90% (maintained from empty days)
        $log2 = MentalCapacityLog::where('user_id', $this->user->id)
            ->where('date', '2025-09-20')
            ->first();
        $this->assertEquals(90, $log2->capacity_before, 'Empty days maintain capacity at 90%');
        $this->assertEquals(70, $log2->capacity_after, '90% - 20% = 70%');
    }
}
