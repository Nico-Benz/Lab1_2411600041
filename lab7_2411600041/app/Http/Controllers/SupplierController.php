<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SearchService;
use App\Services\SupplierPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function __construct(private SearchService $search)
    {
    }

    /** List with pagination, search, active filter and sorting */
    public function index(Request $request)
    {
        $suppliers = $this->search->suppliers($request)
            ->withCount('purchaseOrders')
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['is_active'] = $request->boolean('is_active');

        $supplier = Supplier::create($validated);

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Supplier added successfully.');
    }

    public function show(Supplier $supplier, SupplierPerformanceService $performance)
    {
        $supplier->load(['suppliedProducts', 'stickyNotes.user']);

        $recentOrders = $supplier->purchaseOrders()->withCount('items')->latest('order_date')->latest('id')->take(8)->get();
        $metrics = $performance->forSupplier($supplier);

        return view('suppliers.show', compact('supplier', 'recentOrders', 'metrics'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate($this->rules($supplier), $this->messages());
        $validated['is_active'] = $request->boolean('is_active');

        $supplier->update($validated);

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Supplier updated successfully.');
    }

    /** Soft delete. A supplier with open purchase orders cannot be deleted. */
    public function destroy(Supplier $supplier)
    {
        $open = $supplier->openOrdersCount();

        if ($open > 0) {
            return redirect()->route('suppliers.index')
                ->with('error', "Cannot delete {$supplier->name}: it still has {$open} open purchase order(s).");
        }

        $supplier->delete(); // only fills deleted_at

        return redirect()->route('suppliers.index')
            ->with('success', "{$supplier->name} was deleted. Old purchase orders still show its name, and an admin can restore it.");
    }

    /** Bring a soft-deleted supplier back */
    public function restore(int $id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $supplier->restore();

        return redirect()->route('suppliers.index')
            ->with('success', "{$supplier->name} was restored.");
    }

    private function rules(?Supplier $supplier = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplier?->id)],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'address' => 'nullable|string|max:500',
            'payment_terms' => 'required|string|max:50',
            'lead_time_days' => 'required|integer|min:0|max:365',
            'minimum_order_amount' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Please enter the company name.',
            'contact_person.required' => 'Please enter the contact person.',
            'email.required' => 'Please enter the email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already used by another supplier (it may be a deleted one).',
            'phone.required' => 'Please enter the phone number.',
            'phone.regex' => 'Phone number may only have digits, spaces, +, - and parentheses (7 to 20 characters).',
            'payment_terms.required' => 'Please enter the payment terms (e.g. Net 30, COD).',
            'lead_time_days.required' => 'Please enter the lead time in days.',
            'lead_time_days.integer' => 'Lead time must be a whole number of days.',
            'minimum_order_amount.required' => 'Please enter the minimum order amount (0 if none).',
            'minimum_order_amount.numeric' => 'Minimum order amount must be a number.',
        ];
    }
}
