<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated user routes (active accounts only)
Route::middleware(['auth', 'active'])->group(function () {
    // User dashboard (lecturer + team)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Booking flow
    Route::get('/booking/create', fn() => view('booking.create'))->name('booking.create');
    Route::get('/booking/create/logbook', fn() => view('booking.logbook'))->name('booking.logbook');
    Route::get('/booking/create/schedule', fn() => view('booking.schedule'))->name('booking.schedule');
    Route::get('/booking/create/review', fn() => view('booking.review'))->name('booking.review');
    Route::get('/booking/history', fn() => view('booking.history'))->name('booking.history');
    Route::get('/booking/{id}', fn($id) => view('booking.show', ['id' => $id]))->name('booking.show');

    // Profile (Breeze default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin-only routes
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
