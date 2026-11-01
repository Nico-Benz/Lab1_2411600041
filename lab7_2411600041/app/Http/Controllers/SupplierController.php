<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        // Search: company name, contact person, email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter: active / inactive
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Sort: only allowed columns
        $allowedSorts = ['company_name', 'lead_time_days', 'created_at'];
        $sort = in_array($request->sort, $allowedSorts) ? $request->sort : 'company_name';
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $direction);

        $suppliers = $query->paginate(10)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'         => 'required|string|max:255',
            'contact_person'       => 'required|string|max:255',
            'email'                => 'required|email|max:255|unique:suppliers,email',
            'phone'                => ['nullable', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'address'              => 'nullable|string|max:1000',
            'payment_terms'        => 'required|string|max:50',
            'lead_time_days'       => 'required|integer|min:0|max:365',
            'minimum_order_amount' => 'required|numeric|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Supplier::create($validated);

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        // Subjects (products) supplied by this instructor
        $products = $supplier->products()->orderBy('subject_code')->get();

        // 5 pinakabagong purchase orders ng supplier
        $recentOrders = $supplier->purchaseOrders()
            ->latest('order_date')
            ->latest('id')
            ->take(5)
            ->get();

        // Performance metrics
        $received = $supplier->purchaseOrders()
            ->where('status', 'received')
            ->whereNotNull('received_at')
            ->get();

        $metrics = [
            'total_orders'      => $supplier->purchaseOrders()->count(),
            'received_orders'   => $received->count(),
            'rating'            => $supplier->performance_rating,
            'avg_delivery_days' => $received->isEmpty()
                ? null
                : round($received->avg(fn ($po) => $po->order_date->copy()->startOfDay()
                    ->diffInDays($po->received_at->copy()->startOfDay())), 1),
            'quoted_days'       => $supplier->lead_time_days,
            'total_value'       => $supplier->purchaseOrders()
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->sum('total'),
        ];

        return view('suppliers.show', compact('supplier', 'products', 'recentOrders', 'metrics'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'company_name'         => 'required|string|max:255',
            'contact_person'       => 'required|string|max:255',
            'email'                => ['required', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplier->id)],
            'phone'                => ['nullable', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'address'              => 'nullable|string|max:1000',
            'payment_terms'        => 'required|string|max:50',
            'lead_time_days'       => 'required|integer|min:0|max:365',
            'minimum_order_amount' => 'required|numeric|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $supplier->update($validated);

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        // Bawal burahin kung may PO pa na hindi tapos (pending, approved, sent)
        $hasOpenOrders = $supplier->purchaseOrders()
            ->whereIn('status', ['pending', 'approved', 'sent'])
            ->exists();

        if ($hasOpenOrders) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Cannot delete this supplier: there are still open purchase orders.');
        }

        $supplier->delete(); // soft delete (sets deleted_at)

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}
