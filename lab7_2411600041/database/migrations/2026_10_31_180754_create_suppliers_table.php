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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('payment_terms', 50)->default('Net 30');
            $table->unsignedSmallInteger('lead_time_days')->default(7);
            $table->decimal('minimum_order_amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
