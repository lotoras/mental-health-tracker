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
        $logs = MentalCapacityLog::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'asc')
            ->get();

        $breakdowns = $logs->filter(fn($log) => $log->triggered_breakdown);
        $totalBreakdowns = MentalState::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays($days))
            ->whereHas('stateType', fn($q) => $q->where('is_breakdown', true))
            ->count();

        $triggeredByLowCapacity = $breakdowns->count();

        // Get average capacity before breakdowns
        $avgCapacityBeforeBreakdown = $breakdowns->isNotEmpty()
            ? round($breakdowns->avg('capacity_before'))
            : null;

        // Count consecutive stressful days before breakdowns
        $stressStreaks = $this->findStressStreaksBeforeBreakdowns($user, $days);

        return [
            'total_breakdowns' => $totalBreakdowns,
            'triggered_by_low_capacity' => $triggeredByLowCapacity,
            'percentage_triggered' => $totalBreakdowns > 0
                ? round(($triggeredByLowCapacity / $totalBreakdowns) * 100, 1)
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
