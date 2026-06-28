<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            $table->enum('item_type', ['consultation', 'lab', 'bed', 'medicine']);
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
