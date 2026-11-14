<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic: one notes table that can belong to a Supplier OR a PurchaseOrder
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('noteable');                               // noteable_type + noteable_id
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
