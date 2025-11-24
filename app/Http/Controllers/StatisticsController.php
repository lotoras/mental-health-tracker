<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentMonth = $request->input('month', now()->format('Y-m'));
        [$year, $month] = explode('-', $currentMonth);

        $statistics = $this->statisticsService->getMonthlyStatistics(
            $user,
            (int)$year,
            (int)$month
        );

        return Inertia::render('Statistics', [
            'statistics' => $statistics,
            'currentMonth' => $currentMonth,
        ]);
    }
}
