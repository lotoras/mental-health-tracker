<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MentalCapacityService;
use Illuminate\Console\Command;

class RecalculateCapacity extends Command
{
    protected $signature = 'capacity:recalculate {--user= : Specific user ID to recalculate}';

    protected $description = 'Recalculate mental capacity logs from first entry to today for all users (or a specific user)';

    public function __construct(
        private MentalCapacityService $capacityService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $userId = $this->option('user');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return Command::FAILURE;
            }
            $users = collect([$user]);
        } else {
            $users = User::all();
        }

        if ($users->isEmpty()) {
            $this->info('No users found.');
            return Command::SUCCESS;
        }

        $this->info("Recalculating capacity for {$users->count()} user(s)...");
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $this->capacityService->recalculateCapacityFromFirstEntry($user);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Capacity recalculation completed successfully!');

        return Command::SUCCESS;
    }
}
