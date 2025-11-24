<?php

use App\Http\Controllers\{ProfileController, CalendarController, StatisticsController};
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Redirect authenticated users to calendar, guests to login
    if (auth()->check()) {
        return redirect()->route('calendar.index');
    }
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard redirects to calendar
    Route::get('/dashboard', function () {
        return redirect()->route('calendar.index');
    })->name('dashboard');

    // Calendar routes
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/state', [CalendarController::class, 'store'])->name('calendar.store');
    Route::delete('/calendar/state', [CalendarController::class, 'destroy'])->name('calendar.destroy');

    // Statistics routes
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
