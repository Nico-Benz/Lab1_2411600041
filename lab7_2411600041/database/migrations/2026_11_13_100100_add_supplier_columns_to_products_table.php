<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // The old text column "supplier" is replaced by a real relationship
            $table->foreignId('supplier_id')->nullable()->after('unit_price')->constrained()->nullOnDelete();
            $table->unsignedInteger('package_size')->default(1)->after('supplier_id');         // order in multiples of this
            $table->unsignedInteger('min_order_quantity')->default(1)->after('package_size');  // supplier minimum
            $table->dropColumn('supplier');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('supplier')->nullable();
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['package_size', 'min_order_quantity']);
        });
    }
};
