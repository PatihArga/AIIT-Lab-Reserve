<?php

use App\Http\Controllers\Admin\AdminComputerController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\Admin\AdminTeamController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\BookingLogbookController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated user routes (active accounts only)
Route::middleware(['auth', 'active'])->group(function () {
    // User dashboard (lecturer + team)
    Route::get('/dashboard', [BookingController::class, 'dashboard'])->name('dashboard');

    // Calendar (week-view) — primary booking entry point
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Booking creation — inline on the calendar (single POST). The old multi-step flow
    // (schedule / logbook / review) was fully removed in the calendar redesign.
    Route::post('/calendar/booking', [CalendarController::class, 'store'])->name('calendar.booking.store');

    // Booking management
    Route::get ('/booking/history',                  [BookingController::class, 'history'])->name('booking.history');
    Route::get ('/booking/{booking}',                [BookingController::class, 'show'])->name('booking.show');
    Route::post('/booking/{booking}/cancel',         [BookingController::class, 'cancel'])->name('booking.cancel');
    Route::put ('/booking/{booking}/logbook',        [BookingLogbookController::class, 'update'])->name('booking.logbook.update');

    // AJAX availability endpoints (session-authenticated, JSON)
    Route::get('/api/check-availability',  [AvailabilityController::class, 'check'])->name('api.availability.check');
    Route::get('/api/computers/available', [AvailabilityController::class, 'availableComputers'])->name('api.availability.computers');

    // Profile (Breeze default)
    Route::get   ('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch ('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin-only routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Requests
        Route::get ('/requests',                         [AdminRequestController::class, 'index'])->name('requests.index');
        Route::get ('/requests/{booking}',               [AdminRequestController::class, 'show'])->name('requests.show');
        Route::post('/requests/{booking}/approve',       [AdminRequestController::class, 'approve'])->name('requests.approve');
        Route::post('/requests/{booking}/reject',        [AdminRequestController::class, 'reject'])->name('requests.reject');
        Route::post('/requests/{booking}/complete',      [AdminRequestController::class, 'complete'])->name('requests.complete');

        // Computers
        Route::get ('/computers',                        [AdminComputerController::class, 'index'])->name('computers.index');
        Route::post('/computers/{computer}/status',      [AdminComputerController::class, 'updateStatus'])->name('computers.status');

        // Users (lecturers + team accounts)
        Route::get ('/users',                            [AdminUserController::class, 'index'])->name('users.index');
        Route::get ('/users/create',                     [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users',                            [AdminUserController::class, 'store'])->name('users.store');
        Route::get ('/users/{user}/edit',                [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put ('/users/{user}',                     [AdminUserController::class, 'update'])->name('users.update');

        // Teams
        Route::get ('/teams/create',                     [AdminTeamController::class, 'create'])->name('teams.create');
        Route::post('/teams',                            [AdminTeamController::class, 'store'])->name('teams.store');
        Route::get ('/teams/{team}/edit',                [AdminTeamController::class, 'edit'])->name('teams.edit');
        Route::put ('/teams/{team}',                     [AdminTeamController::class, 'update'])->name('teams.update');

        // Phase 8 (still closures — reports, audit log, settings)
        Route::get('/reports', fn() => view('admin.reports.index'))->name('reports.index');
        Route::get('/audit-log', fn() => view('admin.audit-log.index'))->name('audit-log.index');
        Route::get('/settings', fn() => view('admin.settings.index'))->name('settings.index');
    });
});

require __DIR__.'/auth.php';
