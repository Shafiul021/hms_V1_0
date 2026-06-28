<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_logs');
    }
};
