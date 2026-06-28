<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('slot_id')->nullable()->constrained('time_slots')->onDelete('set null');
            $table->date('date');
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('booked_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
