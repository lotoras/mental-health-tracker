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
            'state_breakdown' => $this->getStateBreakdown($states),
            'days_since_last_breakdown' => $this->getDaysSinceLastBreakdown($user),
            'breakdown_duration' => $this->getBreakdownDuration($user),
            'monthly_breakdown_count' => $this->getMonthlyBreakdownCount($user, $year, $month),
            'current_capacity' => $this->capacityService->getCurrentCapacity($user),
            'capacity_timeline' => $this->capacityService->getCapacityTimeline($user, $startDate, $endDate),
            'breakdown_analysis' => $this->capacityService->analyzeBreakdownTriggers($user, 90),
            'capacity_forecast' => $this->capacityService->forecastCapacity($user, 7),
        ];
    }

    private function getStateBreakdown(Collection $states): array
    {
        $breakdown = $states->groupBy('state_key')->map->count();

        // Get all state types to ensure we have all keys
        $stateTypes = MentalStateType::orderBy('order')->get();

        $result = [];
        foreach ($stateTypes as $stateType) {
            $result[$stateType->key] = [
                'count' => $breakdown->get($stateType->key, 0),
                'label' => $stateType->label,
                'color' => $stateType->color,
                'severity' => $stateType->severity,
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

        return Carbon::today()->diffInDays($lastBreakdown->date);
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
