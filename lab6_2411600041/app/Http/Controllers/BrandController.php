<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::withCount('products')->orderBy('name')->get();

        return view('brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('brands.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:brands,name',
            'description' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Please enter the brand name.',
            'name.unique' => 'This brand already exists.',
        ]);

        Brand::create($validated);

        return redirect()->route('brands.index')
            ->with('success', 'Brand added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        $products = $brand->products()->with(['brand', 'category'])->orderBy('name')->get();

        return view('brands.show', compact('brand', 'products'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Brand $brand)
    {
        return view('brands.form', compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:brands,name,' . $brand->id,
            'description' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Please enter the brand name.',
            'name.unique' => 'This brand already exists.',
        ]);

        $brand->update($validated);

        return redirect()->route('brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        // Do not delete if products still use it
        if ($brand->products()->exists()) {
            return redirect()->route('brands.index')
                ->with('error', 'Cannot delete this brand because it still has products.');
        }

        $brand->delete();

        return redirect()->route('brands.index')
            ->with('success', 'Brand deleted successfully.');
    }
}
