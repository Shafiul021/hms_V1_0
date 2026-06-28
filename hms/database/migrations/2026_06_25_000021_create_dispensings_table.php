<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->onDelete('cascade');
            $table->foreignId('pharmacist_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('dispensed_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensings');
    }
};
