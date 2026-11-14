<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->integer('quantity_before')->nullable()->after('quantity');
            $table->integer('quantity_after')->nullable()->after('quantity_before');
            $table->string('reason')->nullable()->after('reference_number');
            $table->text('notes')->nullable()->after('reason');
            $table->index(['product_id', 'created_at']); // faster history and report queries
        });

        // Fill the running balance for old rows (oldest first, per product)
        $balances = [];
        foreach (DB::table('inventory_transactions')->orderBy('id')->get() as $row) {
            $before = $balances[$row->product_id] ?? 0;
            $after = $row->transaction_type === 'in' ? $before + $row->quantity : $before - $row->quantity;
            DB::table('inventory_transactions')->where('id', $row->id)
                ->update(['quantity_before' => $before, 'quantity_after' => $after]);
            $balances[$row->product_id] = $after;
        }
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'created_at']);
            $table->dropColumn(['quantity_before', 'quantity_after', 'reason', 'notes']);
        });
    }
};
