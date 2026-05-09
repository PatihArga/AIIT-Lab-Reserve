<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_logbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();

            // Mandatory fields
            $table->enum('category', [
                'penelitian', 'project_akademik', 'praktikum', 'tugas_akhir', 'lainnya',
            ]);
            $table->text('checkpoint_progress');

            // Optional fields
            $table->string('related_course')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->boolean('duration_sufficient')->nullable();
            $table->text('special_software')->nullable();
            $table->boolean('needs_internet')->nullable();
            $table->boolean('needs_installation')->nullable();
            $table->text('external_devices')->nullable();
            $table->enum('priority_level', ['normal', 'urgent'])->default('normal');
            $table->text('priority_reason')->nullable();
            $table->text('session_target')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_logbooks');
    }
};
