<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $allProducts = Product::all();

        $totalProducts = $allProducts->count();
        $lowStockCount = $allProducts->filter(fn ($p) => $p->isLowStock())->count();
        $outOfStockCount = $allProducts->filter(fn ($p) => $p->isOutOfStock())->count();
        $inventoryValue = $allProducts->sum(fn ($p) => $p->quantity * $p->unit_price);

        // Newest products
        $recentProducts = Product::with('category')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->take(5)->get();

        // Products at or below their reorder level (includes out of stock)
        $lowStockProducts = Product::whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('quantity')->take(5)->get();

        $brands = Brand::withCount('products')->orderBy('name')->get();
        $categories = Category::withCount('products')->orderBy('name')->get();

        $recentTransactions = InventoryTransaction::with(['product', 'user'])->latest()->orderByDesc('id')->take(5)->get();

        $hour = now()->format('H');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');

        return view('dashboard', compact(
            'greeting',
            'totalProducts',
            'lowStockCount',
            'outOfStockCount',
            'inventoryValue',
            'recentProducts',
            'lowStockProducts',
            'brands',
            'categories',
            'recentTransactions'
        ));
    }
}
