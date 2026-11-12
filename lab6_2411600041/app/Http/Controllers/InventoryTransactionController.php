<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class InventoryTransactionController extends Controller
{
    /**
     * Show all stock movements (20 per page, with filters).
     */
    public function index(Request $request)
    {
        $transactions = InventoryTransaction::with(['product', 'user'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('type'), fn ($q) => $q->where('transaction_type', $request->type))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->recent()
            ->paginate(20)
            ->withQueryString();

        $products = Product::orderBy('name')->get(['id', 'name', 'sku']);

        return view('transactions.index', compact('transactions', 'products'));
    }

    /**
     * Form to record a new stock in / stock out.
     */
    public function create(Request $request)
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'quantity', 'reorder_level']);
        $selectedProduct = $request->query('product_id');

        return view('transactions.create', compact('products', 'selectedProduct'));
    }

    /**
     * Record a stock in or stock out.
     * The Observer updates the product quantity.
     */
    public function store(Request $request, ?Product $product = null)
    {
        if ($product) {
            $request->merge(['product_id' => $product->id]);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'transaction_type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ], [
            'product_id.required' => 'Please choose a product.',
            'product_id.exists' => 'The selected product does not exist.',
            'transaction_type.required' => 'Please choose Stock In or Stock Out.',
            'transaction_type.in' => 'Transaction type must be Stock In or Stock Out.',
            'quantity.required' => 'Please enter the quantity.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
        ]);

        // Custom check: stock out cannot be more than what is in stock
        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->isNotEmpty() || $request->transaction_type !== 'out') {
                return;
            }
            $available = Product::find($request->product_id)?->quantity ?? 0;
            if ((int) $request->quantity > $available) {
                $validator->errors()->add('quantity', "Not enough stock. Only {$available} available.");
            }
        });

        $validated = $validator->validate();

        try {
            // Transaction row + product quantity update succeed together, or both roll back
            DB::transaction(function () use ($validated) {
                InventoryTransaction::create([
                    'product_id' => $validated['product_id'],
                    'transaction_type' => $validated['transaction_type'],
                    'quantity' => $validated['quantity'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'user_id' => Auth::id(),
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        $target = $product
            ? redirect()->route('products.show', $product)
            : redirect()->route('transactions.index');

        return $target->with('success', 'Stock movement recorded successfully.');
    }

    /**
     * Show one transaction.
     */
    public function show(InventoryTransaction $transaction)
    {
        $transaction->load(['product.category', 'user']);

        return view('transactions.show', compact('transaction'));
    }

    /**
     * Delete a transaction (admin only).
     * Only the LATEST transaction of a product can be deleted, and the stock change is reversed,
     * so the running balance of older records stays correct.
     */
    public function destroy(InventoryTransaction $transaction)
    {
        $latestId = InventoryTransaction::where('product_id', $transaction->product_id)->max('id');

        if ($transaction->id !== (int) $latestId) {
            return back()->with('error', 'Only the latest transaction of a product can be deleted. Record an adjustment instead.');
        }

        DB::transaction(function () use ($transaction) {
            $product = Product::lockForUpdate()->find($transaction->product_id);
            if ($product && $transaction->quantity_before !== null) {
                $product->quantity = $transaction->quantity_before; // reverse the stock change
                $product->save();
            }
            $transaction->delete();
        });

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted and the stock change was reversed.');
    }
}
