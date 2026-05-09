<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('computers', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('unit_number')->unique();
            $table->string('label', 20);
            $table->enum('status', ['online', 'maintenance', 'offline'])->default('online');
            $table->text('specs_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('computers');
    }
};
