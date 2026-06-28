<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->onDelete('cascade');
            $table->string('batch_no');
            $table->unsignedInteger('quantity');
            $table->date('expiry_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_batches');
    }
};
