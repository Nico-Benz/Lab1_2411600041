<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PdfService;
use App\Services\SearchService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    use AuthorizesRequests; // gives us $this->authorize()

    public function __construct(private SearchService $search)
    {
    }

    /** List with pagination, filters (status, supplier, date range) and eager loading */
    public function index(Request $request)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $orders = $this->search->purchaseOrders($request)
            ->with(['supplier', 'createdBy'])   // eager loading: no extra query for every row
            ->withCount('items')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::withTrashed()->orderBy('name')->get(['id', 'name']);

        return view('purchase-orders.index', compact('orders', 'suppliers'));
    }

    public function create()
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('purchase-orders.create', [
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'productOptions' => $this->productOptions(),
        ]);
    }

    /** Save as draft, or save and send for approval (button "action") */
    public function store(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $data = $this->validated($request);
        $status = $request->input('action') === 'submit' ? 'pending' : 'draft';

        $order = DB::transaction(function () use ($data, $status) {
            $order = PurchaseOrder::create([
                'reference_number' => PurchaseOrder::generateReferenceNumber(),
                'supplier_id' => $data['supplier_id'],
                'status' => $status,
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->saveItems($order, $data['items']);
            $order->calculateTotal();

            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)
            ->with('success', "Purchase order {$order->reference_number} was created as {$status}.");
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'items.product', 'createdBy', 'approvedBy', 'statusLogs.user', 'stickyNotes.user']);

        return view('purchase-orders.show', ['order' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->load('items');

        return view('purchase-orders.edit', [
            'order' => $purchaseOrder,
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'productOptions' => $this->productOptions(),
        ]);
    }

    /** Only draft orders can be changed (the policy checks this) */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $data = $this->validated($request);

        DB::transaction(function () use ($data, $purchaseOrder) {
            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            $this->saveItems($purchaseOrder, $data['items']);
            $purchaseOrder->calculateTotal();
        });

        if ($request->input('action') === 'submit') {
            $purchaseOrder->update(['status' => 'pending']);

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order updated and sent for approval.');
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order updated.');
    }

    /** Soft delete (draft orders, admin only) */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('delete', $purchaseOrder);

        $reference = $purchaseOrder->reference_number;
        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('success', "Purchase order {$reference} was deleted.");
    }

    // ---------------- Workflow actions ----------------

    /** draft -> pending */
    public function submit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('submit', $purchaseOrder);

        if (! $purchaseOrder->items()->exists()) {
            return back()->with('error', 'Add at least one line item before sending the order for approval.');
        }

        $purchaseOrder->update(['status' => 'pending']);

        return back()->with('success', 'Purchase order was sent for approval.');
    }

    /** pending -> approved */
    public function approve(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('approve', $purchaseOrder);

        if (! $purchaseOrder->canBeApproved()) {
            return back()->with('error', 'Only pending purchase orders can be approved.');
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', "Purchase order {$purchaseOrder->reference_number} was approved.");
    }

    /** pending -> rejected (with a reason) */
    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('approve', $purchaseOrder);

        $data = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Please write the reason for rejecting this order.',
        ]);

        if (! $purchaseOrder->canBeApproved()) {
            return back()->with('error', 'Only pending purchase orders can be rejected.');
        }

        $purchaseOrder->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return back()->with('success', "Purchase order {$purchaseOrder->reference_number} was rejected.");
    }

    /** approved -> sent (the supplier gets an email) */
    public function send(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('send', $purchaseOrder);

        if (! $purchaseOrder->canBeSent()) {
            return back()->with('error', 'Only approved purchase orders can be marked as sent.');
        }

        $purchaseOrder->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('success', "Purchase order {$purchaseOrder->reference_number} was marked as sent to {$purchaseOrder->supplier->name}.");
    }

    /**
     * Receive items: add the received quantity to the order line,
     * and create a Stock In transaction for every product (the observer updates the stock).
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('receive', $purchaseOrder);

        $purchaseOrder->load(['items.product', 'supplier']);

        if (! $purchaseOrder->canBeReceived()) {
            return back()->with('error', 'This purchase order cannot receive items. It must be sent and still have outstanding items.');
        }

        $request->validate([
            'received' => 'required|array',
            'received.*' => 'nullable|integer|min:0',
        ], [
            'received.*.integer' => 'Received quantity must be a whole number.',
            'received.*.min' => 'Received quantity cannot be negative.',
        ]);

        $errors = [];
        $lines = [];

        foreach ($purchaseOrder->items as $item) {
            $quantity = (int) $request->input("received.{$item->id}", 0);

            if ($quantity > $item->outstanding_quantity) {
                $errors["received.{$item->id}"] = "{$item->product->name}: only {$item->outstanding_quantity} still outstanding.";
            } elseif ($quantity > 0) {
                $lines[] = [$item, $quantity];
            }
        }

        if ($errors) {
            return back()->withErrors($errors)->withInput();
        }

        if (! $lines) {
            return back()->withErrors(['received' => 'Enter the received quantity for at least one item.'])->withInput();
        }

        DB::transaction(function () use ($purchaseOrder, $lines) {
            $summary = [];

            foreach ($lines as [$item, $quantity]) {
                InventoryTransaction::create([
                    'product_id' => $item->product_id,
                    'transaction_type' => 'in',
                    'quantity' => $quantity,
                    'reference_number' => $purchaseOrder->reference_number,
                    'reason' => 'Purchase order receipt',
                    'notes' => "Received from {$purchaseOrder->supplier->name}",
                    'user_id' => Auth::id(),
                ]);

                $item->increment('received_quantity', $quantity);
                $summary[] = "{$quantity} x {$item->product->name}";
            }

            $purchaseOrder->addLog('items_received', $purchaseOrder->status, $purchaseOrder->status, implode(', ', $summary), Auth::id());

            $purchaseOrder->load('items');
            $allReceived = $purchaseOrder->items->every(fn ($item) => $item->outstanding_quantity === 0);

            if ($allReceived) {
                $purchaseOrder->update(['status' => 'received', 'received_at' => now()]);
            }
        });

        $purchaseOrder->refresh();

        return back()->with('success', $purchaseOrder->status === 'received'
            ? 'All items received. The purchase order is now complete and the stock was updated.'
            : 'Items received and the stock was updated. The order still has outstanding items.');
    }

    /** draft / pending / approved -> cancelled */
    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('cancel', $purchaseOrder);

        if (! $purchaseOrder->canBeCancelled()) {
            return back()->with('error', 'This purchase order can no longer be cancelled.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return back()->with('success', "Purchase order {$purchaseOrder->reference_number} was cancelled.");
    }

    /** One purchase order as a PDF */
    public function pdf(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        if (! PdfService::available()) {
            return back()->with('error', 'PDF needs the dompdf package. Run this in the terminal: composer require barryvdh/laravel-dompdf');
        }

        $purchaseOrder->load(['supplier', 'items.product', 'createdBy']);

        return PdfService::download('pdf.purchase-order', ['po' => $purchaseOrder], "{$purchaseOrder->reference_number}.pdf");
    }

    // ---------------- Helpers ----------------

    private function validated(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|distinct|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'supplier_id.required' => 'Please choose a supplier.',
            'supplier_id.exists' => 'Please choose an active supplier.',
            'order_date.required' => 'Please enter the order date.',
            'expected_delivery_date.after_or_equal' => 'The expected delivery date cannot be before the order date.',
            'items.required' => 'Add at least one line item.',
            'items.min' => 'Add at least one line item.',
            'items.*.product_id.required' => 'Please choose a product for every line.',
            'items.*.product_id.distinct' => 'The same product is added twice. Combine them into one line.',
            'items.*.quantity.required' => 'Please enter the quantity for every line.',
            'items.*.quantity.integer' => 'Quantity must be a whole number.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.unit_price.required' => 'Please enter the unit price for every line.',
            'items.*.unit_price.numeric' => 'Unit price must be a number.',
        ]);
    }

    /** Replace all lines of the order with the submitted ones */
    private function saveItems(PurchaseOrder $order, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],   // the price is saved on the line at the time of the order
            ]);
        }
    }

    /** Data for the product dropdown of the form (JavaScript uses it to fill the prices) */
    private function productOptions(): array
    {
        return Product::with('suppliers')->orderBy('name')->get()->map(function (Product $product) {
            $prices = [];

            foreach ($product->suppliers as $supplier) {
                $prices[$supplier->id] = (float) ($supplier->pivot->supplier_price ?? $product->unit_price);
            }

            if ($product->supplier_id && ! isset($prices[$product->supplier_id])) {
                $prices[$product->supplier_id] = (float) $product->unit_price;
            }

            return [
                'id' => $product->id,
                'label' => "{$product->sku} — {$product->name}",
                'price' => (float) $product->unit_price,
                'prices' => (object) $prices,
                'package_size' => (int) $product->package_size,
                'min_order_quantity' => (int) $product->min_order_quantity,
            ];
        })->values()->all();
    }
}
