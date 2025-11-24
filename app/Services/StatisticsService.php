<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{MentalState, MentalStateType, User};
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StatisticsService
{
    public function __construct(
        private MentalCapacityService $capacityService
    ) {}

    public function getMonthlyStatistics(User $user, int $year, int $month): array
    {
        $states = MentalState::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with('stateType')
            ->get();

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        return [
            'total_entries' => $states->count(),
            'state_breakdown' => $this->getStateBreakdown($states, $startDate, $endDate),
            'days_since_last_breakdown' => $this->getDaysSinceLastBreakdown($user),
            'breakdown_duration' => $this->getBreakdownDuration($user),
            'monthly_breakdown_count' => $this->getMonthlyBreakdownCount($user, $year, $month),
            'current_capacity' => $this->capacityService->getCurrentCapacity($user),
            'capacity_timeline' => $this->capacityService->getCapacityTimeline($user, $startDate, $endDate),
            'breakdown_analysis' => $this->capacityService->analyzeBreakdownTriggers($user, 90),
            'capacity_forecast' => $this->capacityService->forecastCapacity($user, 7),
        ];
    }

    public function getAllTimeStatistics(User $user): array
    {
        // Get all states for this user
        $states = MentalState::where('user_id', $user->id)
            ->with('stateType')
            ->get();

        // Get the first and last entry dates
        $firstEntry = $states->min('date');
        $lastEntry = $states->max('date') ?? Carbon::today();

        $startDate = $firstEntry ? Carbon::parse($firstEntry) : Carbon::today();
        $endDate = Carbon::parse($lastEntry);

        // Count all breakdown days (not just this month)
        $totalBreakdowns = MentalState::where('user_id', $user->id)
            ->whereHas('stateType', fn($q) => $q->where('is_breakdown', true))
            ->count();

        return [
            'total_entries' => $states->count(),
            'state_breakdown' => $this->getStateBreakdown($states, $startDate, $endDate),
            'days_since_last_breakdown' => $this->getDaysSinceLastBreakdown($user),
            'breakdown_duration' => $this->getBreakdownDuration($user),
            'monthly_breakdown_count' => $totalBreakdowns, // All-time breakdown count
            'current_capacity' => $this->capacityService->getCurrentCapacity($user),
            'capacity_timeline' => $this->capacityService->getCapacityTimeline($user, $startDate, $endDate),
            'breakdown_analysis' => $this->capacityService->analyzeBreakdownTriggers($user, 90),
            'capacity_forecast' => $this->capacityService->forecastCapacity($user, 7),
        ];
    }

    private function getStateBreakdown(Collection $states, Carbon $startDate, Carbon $endDate): array
    {
        // Calculate total days in the period
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // If no entries yet, return 100% untracked
        if ($states->isEmpty()) {
            return [
                'untracked' => [
                    'count' => $totalDays,
                    'percentage' => 100.0,
                    'label' => 'Nicht erfasst',
                    'color' => '#d1d5db',
                    'severity' => 0,
                ]
            ];
        }

        // Count tracked days per state
        $breakdown = $states->groupBy('state_key')->map(fn($group) => $group->count());
        $trackedDays = $states->count();
        $untrackedDays = $totalDays - $trackedDays;

        // Get all state types to ensure we have all keys
        $stateTypes = MentalStateType::orderBy('order')->get();

        $result = [];
        foreach ($stateTypes as $stateType) {
            $count = $breakdown->get($stateType->key, 0);

            // Only add states that have at least one entry
            if ($count > 0) {
                $result[$stateType->key] = [
                    'count' => $count,
                    // Calculate percentage based on tracked days only (not total days)
                    'percentage' => round($count / $trackedDays * 100, 1),
                    'label' => $stateType->label,
                    'color' => $stateType->color,
                    'severity' => $stateType->severity,
                ];
            }
        }

        // Add untracked days if any (not included in the percentage chart)
        if ($untrackedDays > 0) {
            $result['untracked'] = [
                'count' => $untrackedDays,
                'percentage' => round($untrackedDays / $totalDays * 100, 1),
                'label' => 'Nicht erfasst',
                'color' => '#d1d5db',
                'severity' => 0,
            ];
        }

        return $result;
    }

    private function getDaysSinceLastBreakdown(User $user): ?int
    {
        $lastBreakdown = MentalState::where('user_id', $user->id)
            ->whereHas('stateType', fn($q) => $q->where('is_breakdown', true))
            ->orderBy('date', 'desc')
            ->first();

        if (!$lastBreakdown) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($lastBreakdown->date);
    }

    private function getBreakdownDuration(User $user): int
    {
        $breakdowns = MentalState::where('user_id', $user->id)
            ->whereHas('stateType', fn($q) => $q->where('is_breakdown', true))
            ->orderBy('date', 'desc')
            ->get();

        if ($breakdowns->isEmpty()) {
            return 0;
        }

        $consecutiveDays = 1;
        $previousDate = null;

        foreach ($breakdowns as $breakdown) {
            if ($previousDate === null) {
                $previousDate = $breakdown->date;
                continue;
            }

            if ($breakdown->date->diffInDays($previousDate) === 1) {
                $consecutiveDays++;
                $previousDate = $breakdown->date;
            } else {
                break;
            }
        }

        return $consecutiveDays;
    }

    private function getMonthlyBreakdownCount(User $user, int $year, int $month): int
    {
        return MentalState::where('user_id', $user->id)
            ->whereHas('stateType', fn($q) => $q->where('is_breakdown', true))
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->count();
    }

    public function getAllStateTypes(): Collection
    {
        return MentalStateType::orderBy('order')->get();
    }
}
