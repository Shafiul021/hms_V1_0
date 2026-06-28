<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['draft', 'issued', 'paid', 'partial'])->default('draft');
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->date('due_date');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
