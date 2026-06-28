<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('medicine_id'); // Referenced to medicines table (Day 7)
            $table->string('dosage');
            $table->string('frequency');
            $table->string('duration');
            $table->timestamps();

            $table->index('medicine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
