<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ReorderService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with(['brand', 'category', 'supplier'])->orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('products.index', compact('products', 'brands', 'categories', 'suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('products.form', compact('brands', 'categories', 'suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'package_size' => 'required|integer|min:1',
            'min_order_quantity' => 'required|integer|min:1',
        ], $this->messages());

        $product = Product::create($validated);
        $this->syncSupplier($product);

        return redirect()->route('products.index')
            ->with('success', 'Product added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Product $product, ReorderService $reorder)
    {
        $product->load(['brand', 'category', 'supplier']);

        // Lab 7: what the reorder service calculates for this product
        $reorderPoint = $reorder->calculateReorderPoint($product);
        $suggestedQuantity = $reorder->calculateOptimalOrderQuantity($product);

        // Full history with a date filter. "Balance after" is the running balance.
        $transactions = $product->transactions()
            ->with('user')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->recent()
            ->get();

        return view('products.show', compact('product', 'transactions', 'reorderPoint', 'suggestedQuantity'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('products.form', compact('product', 'brands', 'categories', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'package_size' => 'required|integer|min:1',
            'min_order_quantity' => 'required|integer|min:1',
        ], $this->messages());

        $product->update($validated);
        $this->syncSupplier($product);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Custom error messages for validation failures.
     */
    private function messages(): array
    {
        return [
            'name.required' => 'Please enter the product name.',
            'sku.required' => 'Please enter the SKU.',
            'sku.unique' => 'This SKU is already used by another product.',
            'category_id.required' => 'Please choose a category.',
            'category_id.exists' => 'The selected category does not exist.',
            'brand_id.exists' => 'The selected brand does not exist.',
            'quantity.required' => 'Please enter the quantity in stock.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity cannot be negative.',
            'reorder_level.required' => 'Please enter the reorder level.',
            'reorder_level.integer' => 'Reorder level must be a whole number.',
            'reorder_level.min' => 'Reorder level cannot be negative.',
            'unit_price.required' => 'Please enter the unit price.',
            'unit_price.numeric' => 'Unit price must be a number.',
            'unit_price.min' => 'Unit price cannot be negative.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'package_size.required' => 'Please enter the package size (1 if sold one by one).',
            'package_size.min' => 'Package size must be at least 1.',
            'min_order_quantity.required' => 'Please enter the minimum order quantity.',
            'min_order_quantity.min' => 'Minimum order quantity must be at least 1.',
        ];
    }

    /** Keep the many-to-many table in step with the main supplier */
    private function syncSupplier(Product $product): void
    {
        if ($product->supplier_id) {
            $product->suppliers()->syncWithoutDetaching([$product->supplier_id]);
        }
    }
}
