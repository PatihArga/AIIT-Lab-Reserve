<?php

namespace App\Providers;

use App\Models\LabSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use PDOException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Override session lifetime from lab_settings if available.
        // Wrapped in try/catch so artisan can run before the table exists.
        try {
            if (Schema::hasTable('lab_settings')) {
                $minutes = LabSetting::get('session_lifetime');
                if ($minutes !== null && is_numeric($minutes)) {
                    Config::set('session.lifetime', (int) $minutes);
                }
            }
        } catch (QueryException | PDOException) {
            // Database not reachable (e.g. during fresh install) — fall back to default.
        }
    }
}
