<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MentalState;
use App\Services\{MentalCapacityService, StatisticsService};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService,
        private MentalCapacityService $capacityService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentMonth = $request->input('month', now()->format('Y-m'));
        [$year, $month] = explode('-', $currentMonth);

        // Get states for the month
        $states = MentalState::where('user_id', $user->id)
            ->whereYear('date', (int)$year)
            ->whereMonth('date', (int)$month)
            ->with('stateType')
            ->get()
            ->keyBy(fn($state) => $state->date->format('Y-m-d'));

        // Get all state types for the modal
        $stateTypes = $this->statisticsService->getAllStateTypes();

        // Get current capacity
        $currentCapacity = $this->capacityService->getCurrentCapacity($user);

        return Inertia::render('Calendar', [
            'states' => $states,
            'stateTypes' => $stateTypes,
            'currentMonth' => $currentMonth,
            'currentCapacity' => $currentCapacity,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'state_key' => 'required|exists:mental_state_types,key',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // Create or update mental state
        MentalState::updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => $validated['date'],
            ],
            [
                'state_key' => $validated['state_key'],
                'notes' => $validated['notes'] ?? null,
            ]
        );

        // Recalculate all capacity from the first entry ever to today
        // This ensures empty days are properly tracked as 0%
        $this->capacityService->recalculateCapacityFromFirstEntry($user);

        // Return back to calendar with preserved state
        return back();
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $user = $request->user();

        // Find and delete the mental state
        $mentalState = MentalState::where('user_id', $user->id)
            ->where('date', $validated['date'])
            ->first();

        if ($mentalState) {
            // Delete the state
            $mentalState->delete();

            // Recalculate all capacity from the first entry ever to today
            // This ensures empty days are properly tracked as 0%
            $this->capacityService->recalculateCapacityFromFirstEntry($user);
        }

        // Return back to calendar with preserved state
        return back();
    }
}
