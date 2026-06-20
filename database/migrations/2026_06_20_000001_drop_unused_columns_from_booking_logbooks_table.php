<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop two columns that were never wired up to a working feature:
     *  - priority_reason : declared but never read or written anywhere.
     *  - session_target  : a logbook form field that was removed; no data.
     */
    public function up(): void
    {
        Schema::table('booking_logbooks', function (Blueprint $table) {
            $table->dropColumn(['priority_reason', 'session_target']);
        });
    }

    public function down(): void
    {
        Schema::table('booking_logbooks', function (Blueprint $table) {
            $table->text('priority_reason')->nullable();
            $table->text('session_target')->nullable();
        });
    }
};
