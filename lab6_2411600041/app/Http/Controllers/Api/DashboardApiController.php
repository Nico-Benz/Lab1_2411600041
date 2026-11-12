<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\TransactionResource;
use App\Models\InventoryTransaction;
use App\Models\Product;

class DashboardApiController extends Controller
{
    /** GET /api/dashboard/stats */
    public function stats()
    {
        $products = Product::all();

        return response()->json([
            'stats' => [
                'total_products' => $products->count(),
                'low_stock' => $products->filter(fn ($p) => $p->isLowStock())->count(),
                'out_of_stock' => $products->filter(fn ($p) => $p->isOutOfStock())->count(),
                'inventory_value' => round($products->sum(fn ($p) => $p->quantity * $p->unit_price), 2),
            ],
            'unread_notifications' => auth()->user()->unreadNotifications()->count(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /** GET /api/products/low-stock */
    public function lowStock()
    {
        $products = Product::with('category')->lowStock()->orderBy('quantity')->take(10)->get();

        return ProductResource::collection($products);
    }

    /** GET /api/transactions/recent */
    public function recentTransactions()
    {
        $transactions = InventoryTransaction::with(['product', 'user'])->recent()->take(8)->get();

        return TransactionResource::collection($transactions);
    }

    /** GET /api/products/{product}/stock  (used by the forms to show current stock) */
    public function productStock(Product $product)
    {
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'quantity' => $product->quantity,
            'reorder_level' => $product->reorder_level,
        ]);
    }
}
