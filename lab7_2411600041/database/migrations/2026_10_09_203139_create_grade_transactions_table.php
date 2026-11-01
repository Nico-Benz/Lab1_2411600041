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
            Schema::create('grade_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_item_id')->constrained('grade_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['increase', 'decrease', 'adjustment']);
            $table->decimal('quantity', 5, 2);
            $table->decimal('previous_grade', 5, 2)->nullable();
            $table->decimal('new_grade', 5, 2)->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['grade_item_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_transactions');
    }
};
