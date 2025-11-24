<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{User, MentalState, MentalStateType, MentalCapacityLog};
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MentalCapacityService
{
    private const MAX_CAPACITY = 100;
    private const MIN_CAPACITY = 0;
    private const BREAKDOWN_THRESHOLD = 20; // If capacity drops below this, likely to trigger breakdown

    /**
     * Calculate and log capacity change for a mental state entry
     */
    public function logCapacityChange(MentalState $mentalState): MentalCapacityLog
    {
        $user = $mentalState->user;
        $stateType = MentalStateType::where('key', $mentalState->state_key)->first();

        // Get previous day's capacity
        $previousLog = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', '<', $mentalState->date)
            ->orderBy('date', 'desc')
            ->first();

        $capacityBefore = $previousLog ? $previousLog->capacity_after : self::MAX_CAPACITY;

        // Calculate new capacity
        $capacityChange = $stateType->capacity_impact;
        $capacityAfter = $this->clampCapacity($capacityBefore + $capacityChange);

        // Check if low capacity triggered this breakdown
        $triggeredBreakdown = $stateType->is_breakdown && $capacityBefore <= self::BREAKDOWN_THRESHOLD;

        // Create or update capacity log
        return MentalCapacityLog::updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => $mentalState->date,
            ],
            [
                'mental_state_id' => $mentalState->id,
                'capacity_before' => $capacityBefore,
                'capacity_after' => $capacityAfter,
                'capacity_change' => $capacityChange,
                'triggered_breakdown' => $triggeredBreakdown,
            ]
        );
    }

    /**
     * Recalculate capacity logs from the first entry ever to today (in chronological order)
     * This ensures capacity is always calculated correctly for ALL days.
     * Empty days (days without mental state entries) maintain the previous day's capacity (0% drain).
     */
    public function recalculateCapacityFromFirstEntry(User $user): void
    {
        // Find the first mental state entry ever
        $firstEntry = MentalState::where('user_id', $user->id)
            ->orderBy('date', 'asc')
            ->first();

        // If no entries exist, nothing to calculate
        if (!$firstEntry) {
            return;
        }

        // Get all mental states from the first entry to today
        $states = MentalState::where('user_id', $user->id)
            ->where('date', '>=', $firstEntry->date)
            ->with('stateType')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy(fn($state) => $state->date->format('Y-m-d'));

        // Start at 100% capacity on the first day
        $currentCapacity = self::MAX_CAPACITY;
        $currentDate = $firstEntry->date->copy();
        $today = Carbon::today();

        // Delete all existing capacity logs for this user to start fresh
        MentalCapacityLog::where('user_id', $user->id)->delete();

        // Iterate through each day from first entry to today
        while ($currentDate->lte($today)) {
            $dateKey = $currentDate->format('Y-m-d');
            $capacityBefore = $currentCapacity;

            // Check if user logged a state for this day
            if (isset($states[$dateKey])) {
                $state = $states[$dateKey];
                $stateType = $state->stateType;

                if ($stateType) {
                    $capacityChange = $stateType->capacity_impact;
                    $capacityAfter = $this->clampCapacity($capacityBefore + $capacityChange);
                    $triggeredBreakdown = $stateType->is_breakdown && $capacityBefore <= self::BREAKDOWN_THRESHOLD;

                    MentalCapacityLog::create([
                        'user_id' => $user->id,
                        'date' => $currentDate->copy(),
                        'mental_state_id' => $state->id,
                        'capacity_before' => $capacityBefore,
                        'capacity_after' => $capacityAfter,
                        'capacity_change' => $capacityChange,
                        'triggered_breakdown' => $triggeredBreakdown,
                    ]);

                    $currentCapacity = $capacityAfter;
                }
            } else {
                // Empty day: maintain current capacity (0% drain)
                $capacityAfter = $capacityBefore;

                MentalCapacityLog::create([
                    'user_id' => $user->id,
                    'date' => $currentDate->copy(),
                    'mental_state_id' => null,
                    'capacity_before' => $capacityBefore,
                    'capacity_after' => $capacityAfter,
                    'capacity_change' => 0,
                    'triggered_breakdown' => false,
                ]);

                $currentCapacity = $capacityAfter;
            }

            $currentDate->addDay();
        }
    }

    /**
     * Recalculate capacity logs from a given date forward (in chronological order)
     * This ensures capacity is always calculated correctly, even when entries are added out of order
     * @deprecated Use recalculateCapacityFromFirstEntry instead
     */
    public function recalculateCapacityFrom(User $user, Carbon $fromDate): void
    {
        // Always recalculate from the first entry to ensure accuracy
        $this->recalculateCapacityFromFirstEntry($user);
    }

    /**
     * Get current mental capacity for a user
     */
    public function getCurrentCapacity(User $user): int
    {
        $latestLog = MentalCapacityLog::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->first();

        return $latestLog ? $latestLog->capacity_after : self::MAX_CAPACITY;
    }

    /**
     * Get capacity timeline for charting
     */
    public function getCapacityTimeline(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        return MentalCapacityLog::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($log) {
                return [
                    'date' => $log->date->format('Y-m-d'),
                    'capacity' => $log->capacity_after,
                    'change' => $log->capacity_change,
                ];
            });
    }

    /**
     * Analyze correlation between low capacity and breakdowns
     */
    public function analyzeBreakdownTriggers(User $user, int $days = 90): array
    {
        // Get all mental states for the period (including recent past and near future for testing)
        $startDate = now()->subDays($days);
        $endDate = now()->addDays(7); // Include next 7 days for development/testing

        $states = MentalState::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('stateType')
            ->orderBy('date', 'asc')
            ->get();

        // Find all breakdown states
        $breakdownStates = $states->filter(fn($state) => $state->stateType?->is_breakdown === true);
        $totalBreakdowns = \count($breakdownStates);

        // Calculate capacity before each breakdown by simulating the capacity timeline
        $capacitiesBeforeBreakdowns = [];
        $triggeredByLowCapacityCount = 0;
        $currentCapacity = self::MAX_CAPACITY; // Start at 100%

        foreach ($states as $state) {
            // Skip if state type is not loaded
            if (!$state->stateType) {
                continue;
            }

            if ($state->stateType->is_breakdown === true) {
                // Record capacity BEFORE this breakdown was logged
                $capacitiesBeforeBreakdowns[] = $currentCapacity;

                // Check if this breakdown was triggered by low capacity
                if ($currentCapacity <= self::BREAKDOWN_THRESHOLD) {
                    $triggeredByLowCapacityCount++;
                }
            }

            // Update capacity after processing this state
            $currentCapacity = $this->clampCapacity($currentCapacity + $state->stateType->capacity_impact);
        }

        // Calculate average capacity before breakdowns
        $avgCapacityBeforeBreakdown = \count($capacitiesBeforeBreakdowns) > 0
            ? round(array_sum($capacitiesBeforeBreakdowns) / \count($capacitiesBeforeBreakdowns))
            : null;

        // Count consecutive stressful days before breakdowns
        $stressStreaks = $this->findStressStreaksBeforeBreakdowns($user, $days);

        return [
            'total_breakdowns' => $totalBreakdowns,
            'triggered_by_low_capacity' => $triggeredByLowCapacityCount,
            'percentage_triggered' => $totalBreakdowns > 0
                ? round(($triggeredByLowCapacityCount / $totalBreakdowns) * 100, 1)
                : 0,
            'avg_capacity_before_breakdown' => $avgCapacityBeforeBreakdown,
            'avg_stress_streak_before_breakdown' => $stressStreaks['avg_streak'],
            'longest_stress_streak' => $stressStreaks['longest_streak'],
        ];
    }

    /**
     * Find patterns of stressful days before breakdowns
     */
    private function findStressStreaksBeforeBreakdowns(User $user, int $days): array
    {
        $states = MentalState::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays($days))
            ->with('stateType')
            ->orderBy('date', 'asc')
            ->get();

        $streaks = [];
        $currentStreak = 0;
        $longestStreak = 0;

        foreach ($states as $state) {
            // Count as stressful if it drains capacity
            if ($state->stateType->capacity_impact < 0) {
                $currentStreak++;
                $longestStreak = max($longestStreak, $currentStreak);
            } else if ($state->stateType->is_breakdown) {
                // Breakdown found, save the streak before it
                if ($currentStreak > 0) {
                    $streaks[] = $currentStreak;
                }
                $currentStreak = 0;
            } else {
                $currentStreak = 0;
            }
        }

        return [
            'avg_streak' => count($streaks) > 0 ? round(array_sum($streaks) / count($streaks), 1) : 0,
            'longest_streak' => $longestStreak,
        ];
    }

    /**
     * Get capacity forecast for next N days based on patterns
     */
    public function forecastCapacity(User $user, int $daysAhead = 7): array
    {
        $currentCapacity = $this->getCurrentCapacity($user);

        // Get average daily capacity change from last 30 days
        $recentLogs = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(30))
            ->get();

        $avgDailyChange = $recentLogs->isNotEmpty()
            ? $recentLogs->avg('capacity_change')
            : 0;

        $forecast = [];
        $projectedCapacity = $currentCapacity;

        for ($i = 1; $i <= $daysAhead; $i++) {
            $projectedCapacity = $this->clampCapacity($projectedCapacity + $avgDailyChange);
            $forecast[] = [
                'date' => now()->addDays($i)->format('Y-m-d'),
                'projected_capacity' => round($projectedCapacity),
                'risk_level' => $this->getRiskLevel($projectedCapacity),
            ];
        }

        return $forecast;
    }

    /**
     * Clamp capacity between min and max values
     */
    private function clampCapacity(float $capacity): int
    {
        return (int) max(self::MIN_CAPACITY, min(self::MAX_CAPACITY, $capacity));
    }

    /**
     * Get risk level based on capacity
     */
    private function getRiskLevel(float $capacity): string
    {
        return match(true) {
            $capacity >= 70 => 'low',
            $capacity >= 40 => 'medium',
            $capacity >= self::BREAKDOWN_THRESHOLD => 'high',
            default => 'critical',
        };
    }
}
