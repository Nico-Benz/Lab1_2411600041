<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockAdjustmentController extends Controller
{
    public const REASONS = [
        'Physical count discrepancy',
        'Damaged goods',
        'Theft / loss',
        'System error',
        'Other',
    ];

    public function create()
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'quantity']);
        $reasons = self::REASONS;

        return view('adjustments.create', compact('products', 'reasons'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'physical_count' => 'required|integer|min:0',
            'reason' => 'required|in:' . implode(',', self::REASONS),
            'notes' => 'nullable|string|max:500',
        ], [
            'product_id.required' => 'Please choose a product.',
            'physical_count.required' => 'Please enter the physical count.',
            'physical_count.integer' => 'Physical count must be a whole number.',
            'physical_count.min' => 'Physical count cannot be negative.',
            'reason.required' => 'Please choose a reason.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $system = Product::find($request->product_id)->quantity;
            if ((int) $request->physical_count === (int) $system) {
                $validator->errors()->add('physical_count', "The system quantity is already {$system}. No adjustment is needed.");
            }
        });

        $data = $validator->validate();

        $transaction = DB::transaction(function () use ($data) {
            // The observer calculates the difference and updates the product quantity
            return InventoryTransaction::create([
                'product_id' => $data['product_id'],
                'transaction_type' => 'adjustment',
                'quantity' => 0,
                'quantity_after' => $data['physical_count'],
                'reference_number' => 'ADJ-' . now()->format('Ymd-His'),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'user_id' => Auth::id(),
            ]);
        });

        $sign = $transaction->signed_change >= 0 ? '+' : '';

        return redirect()->route('transactions.index')
            ->with('success', "Adjustment recorded: {$sign}{$transaction->signed_change} (now {$transaction->quantity_after}).");
    }
}
