<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->foreign('medicine_id')->references('id')->on('medicines')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropForeign(['medicine_id']);
        });
    }
};
