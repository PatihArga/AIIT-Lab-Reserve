<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('booking_type', ['full_room', 'computers_only', 'room_only']);
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', [
                'draft', 'submitted', 'under_review',
                'approved', 'rejected', 'cancelled', 'completed',
            ])->default('draft');
            $table->text('admin_notes')->nullable();
            $table->string('google_event_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'start_time', 'end_time']);
            $table->index(['status', 'date']);
        });

        Schema::create('booking_computers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('computer_id')->constrained('computers')->restrictOnDelete();
            $table->unique(['booking_id', 'computer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_computers');
        Schema::dropIfExists('bookings');
    }
};
