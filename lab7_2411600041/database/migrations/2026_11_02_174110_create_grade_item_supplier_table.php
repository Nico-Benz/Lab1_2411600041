<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table para sa many-to-many:
     * isang subject ay puwedeng may maraming alternate supplier (instructor),
     * at isang supplier ay puwedeng may maraming alternate subject.
     */
    public function up(): void
    {
        Schema::create('grade_item_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();

            $table->unique(['grade_item_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_item_supplier');
    }
};