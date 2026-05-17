<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\BookingController;
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

    // Booking creation flow (3 steps + final submit)
    Route::get ('/booking/create',           fn () => redirect()->route('booking.schedule'))->name('booking.create');
    Route::get ('/booking/create/schedule',  [BookingController::class, 'showSchedule'])->name('booking.schedule');
    Route::get ('/booking/create/logbook',   [BookingController::class, 'showLogbook'])->name('booking.logbook');
    Route::get ('/booking/create/review',    [BookingController::class, 'showReview'])->name('booking.review');
    Route::post('/booking',                  [BookingController::class, 'store'])->name('booking.store');

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

    // Admin-only routes (still using closures — Phase 6 will wire these)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');

        // Requests
        Route::get('/requests', fn() => view('admin.requests.index'))->name('requests.index');
        Route::get('/requests/{id}', fn($id) => view('admin.requests.show', ['id' => $id]))->name('requests.show');

        // Computers
        Route::get('/computers', fn() => view('admin.computers.index'))->name('computers.index');

        // Users & Teams
        Route::get('/users', fn() => view('admin.users.index'))->name('users.index');
        Route::get('/users/create', fn() => view('admin.users.create'))->name('users.create');
        Route::get('/users/{id}/edit', fn($id) => view('admin.users.edit', ['id' => $id]))->name('users.edit');
        Route::get('/teams/create', fn() => view('admin.teams.create'))->name('teams.create');

        // Reports, Audit Log, Settings
        Route::get('/reports', fn() => view('admin.reports.index'))->name('reports.index');
        Route::get('/audit-log', fn() => view('admin.audit-log.index'))->name('audit-log.index');
        Route::get('/settings', fn() => view('admin.settings.index'))->name('settings.index');
    });
});

require __DIR__.'/auth.php';
