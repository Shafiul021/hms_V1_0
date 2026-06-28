<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->onDelete('cascade');
            $table->foreignId('nurse_id')->constrained('users')->onDelete('cascade');
            $table->text('note');
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_notes');
    }
};
