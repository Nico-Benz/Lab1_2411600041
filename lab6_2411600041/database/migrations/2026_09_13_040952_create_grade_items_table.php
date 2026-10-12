<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grade_items', function (Blueprint $table) {
        $table->id();
        $table->string('subject_code')->unique();
        $table->string('subject_name');
        $table->text('description')->nullable();
        $table->string('category');
        $table->decimal('current_grade', 5, 2)->default(0);
        $table->decimal('low_grade_threshold', 5, 2)->default(75);
        $table->integer('units')->default(3);
        $table->string('instructor')->nullable();
        $table->string('status')->default('info');
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_items');
    }
};
