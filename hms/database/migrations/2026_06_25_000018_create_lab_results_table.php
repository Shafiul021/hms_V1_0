<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
            $table->string('result_file');
            $table->text('notes')->nullable();
            $table->timestamp('result_at')->useCurrent();
            $table->boolean('is_abnormal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
