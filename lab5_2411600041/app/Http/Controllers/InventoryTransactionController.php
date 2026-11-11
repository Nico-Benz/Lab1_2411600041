<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryTransactionController extends Controller
{
    /**
     * Show all stock movements.
     */
    public function index()
    {
        $transactions = InventoryTransaction::with(['product', 'user'])
            ->latest()
            ->orderByDesc('id')
            ->get();

        return view('transactions.index', compact('transactions'));
    }

    /**
     * Record a stock in or stock out for one product.
     */
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'transaction_type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reference_number' => 'nullable|string|max:100',
        ], [
            'transaction_type.required' => 'Please choose Stock In or Stock Out.',
            'transaction_type.in' => 'Transaction type must be Stock In or Stock Out.',
            'quantity.required' => 'Please enter the quantity.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
        ]);

        // Cannot take out more than what is in stock
        if ($validated['transaction_type'] === 'out' && $validated['quantity'] > $product->quantity) {
            return back()
                ->withErrors(['quantity' => 'Not enough stock. Only ' . $product->quantity . ' available.'])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $product) {
            if ($validated['transaction_type'] === 'in') {
                $product->quantity += $validated['quantity'];
            } else {
                $product->quantity -= $validated['quantity'];
            }
            $product->save();

            $product->transactions()->create([
                'transaction_type' => $validated['transaction_type'],
                'quantity' => $validated['quantity'],
                'reference_number' => $validated['reference_number'] ?? null,
                'user_id' => Auth::id(),
            ]);
        });

        return redirect()->route('products.show', $product)
            ->with('success', 'Stock movement recorded successfully.');
    }
}
