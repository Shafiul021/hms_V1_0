<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['general', 'icu', 'private']);
            $table->unsignedInteger('capacity');
            $table->decimal('daily_rate', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};
