<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('test_id')->constrained('lab_tests')->onDelete('cascade');
            $table->enum('status', ['requested', 'processing', 'completed'])->default('requested');
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_requests');
    }
};
