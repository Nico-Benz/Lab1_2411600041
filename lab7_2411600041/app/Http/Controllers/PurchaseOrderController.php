<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\GradeItem;
use App\Models\GradeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Services\SearchService;

class PurchaseOrderController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, SearchService $search)
    {
        $this->authorize('viewAny', PurchaseOrder::class);
        // Ang SearchService ang naghahanap (gamit ang scopeFilter ng PurchaseOrder)
        $orders = $search->purchaseOrders(
            $request->only(['search', 'supplier_id', 'status', 'date_from', 'date_to'])
        );

        // withTrashed: para makita pa rin sa filter ang supplier na na-soft delete pero may lumang PO
        $suppliers = Supplier::withTrashed()->orderBy('company_name')->get();

        $statuses = ['draft', 'pending', 'approved', 'rejected', 'sent', 'received', 'cancelled'];

        return view('purchase-orders.index', compact('orders', 'suppliers', 'statuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', PurchaseOrder::class);
        // Active suppliers lang ang pwedeng pagpilian
        $suppliers = Supplier::active()->orderBy('company_name')->get();

        // Subjects (products). Dala ang supplier_id para ma-filter sa JavaScript
        $products = GradeItem::orderBy('subject_code')
            ->get(['id', 'subject_code', 'subject_name', 'supplier_id']);

        return view('purchase-orders.create', compact('suppliers', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);
        $validated = $request->validate([
            // Dapat existing at active ang supplier, at hindi soft-deleted
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')
                ->where('is_active', true)
                ->whereNull('deleted_at')],
            'order_date'             => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'tax'                    => 'nullable|numeric|min:0',
            'notes'                  => 'nullable|string|max:2000',
            'terms'                  => 'nullable|string|max:2000',
            // Kahit isang line item
            'items'                  => 'required|array|min:1',
            'items.*.grade_item_id'  => 'required|exists:grade_items,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
        ]);

        // Draft o pending ang unang status, depende sa pinindot na button
        $status = $request->input('action') === 'submit' ? 'pending' : 'draft';

        $order = DB::transaction(function () use ($validated, $status) {
            $order = PurchaseOrder::create([
                'reference_number'       => PurchaseOrder::generateReferenceNumber(),
                'supplier_id'            => $validated['supplier_id'],
                'status'                 => $status,
                'order_date'             => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'tax'                    => $validated['tax'] ?? 0,
                'notes'                  => $validated['notes'] ?? null,
                'terms'                  => $validated['terms'] ?? null,
                'created_by'             => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'grade_item_id'    => $item['grade_item_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price'       => $item['unit_price'],
                ]);
            }

            $order->calculateTotal();

            return $order;
        });

        if ($order->status === 'pending') {
            $this->notifyApprovers($order);
        }

        return redirect()->route('purchase-orders.show', $order)
            ->with('success', 'Purchase order ' . $order->reference_number . ' created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        // Eager loading: kunin agad ang lahat ng kailangan ng page
        $purchaseOrder->load(['supplier', 'createdBy', 'approvedBy', 'items.product']);

        return view('purchase-orders.show', ['order' => $purchaseOrder]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);
        // Draft lang ang pwedeng i-edit
        if ($purchaseOrder->status !== 'draft') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only draft purchase orders can be edited.');
        }

        $purchaseOrder->load('items');

        $suppliers = Supplier::active()->orderBy('company_name')->get();
        $products = GradeItem::orderBy('subject_code')
            ->get(['id', 'subject_code', 'subject_name', 'supplier_id']);

        return view('purchase-orders.edit', [
            'order'     => $purchaseOrder,
            'suppliers' => $suppliers,
            'products'  => $products,
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);
        // Draft lang ang pwedeng i-update
        if ($purchaseOrder->status !== 'draft') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only draft purchase orders can be updated.');
        }

        $validated = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')
                ->where('is_active', true)
                ->whereNull('deleted_at')],
            'order_date'               => 'required|date',
            'expected_delivery_date'   => 'nullable|date|after_or_equal:order_date',
            'tax'                      => 'nullable|numeric|min:0',
            'notes'                    => 'nullable|string|max:2000',
            'terms'                    => 'nullable|string|max:2000',
            'items'                    => 'required|array|min:1',
            'items.*.grade_item_id'    => 'required|exists:grade_items,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_price'       => 'required|numeric|min:0',
        ]);

        $status = $request->input('action') === 'submit' ? 'pending' : 'draft';

        DB::transaction(function () use ($validated, $status, $purchaseOrder) {
            $purchaseOrder->update([
                'supplier_id'            => $validated['supplier_id'],
                'status'                 => $status,
                'order_date'             => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'tax'                    => $validated['tax'] ?? 0,
                'notes'                  => $validated['notes'] ?? null,
                'terms'                  => $validated['terms'] ?? null,
            ]);

            // Draft pa lang, kaya ligtas burahin at gawing muli ang mga linya
            $purchaseOrder->items()->delete();

            foreach ($validated['items'] as $item) {
                $purchaseOrder->items()->create([
                    'grade_item_id'    => $item['grade_item_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price'       => $item['unit_price'],
                ]);
            }

            $purchaseOrder->calculateTotal();
        });

        // Kung na-submit na for approval, ipaalam sa mga approver
        if ($purchaseOrder->status === 'pending') {
            $this->notifyApprovers($purchaseOrder);
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('delete', $purchaseOrder);
        // Hindi binubura ang PO. Kino-cancel lang, para manatili ang audit trail.
        if ($purchaseOrder->status !== 'draft') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only draft purchase orders can be cancelled.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order ' . $purchaseOrder->reference_number . ' cancelled.');
    }

    /**
     * Approve a pending purchase order.
     */
    public function approve(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('approve', $purchaseOrder);
        if (! $purchaseOrder->canBeApproved()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only pending purchase orders can be approved.');
        }

        $purchaseOrder->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Ipaalam sa gumawa ng PO na na-approve na
        $creatorEmail = $purchaseOrder->createdBy?->email;
        if ($creatorEmail) {
            Mail::to($creatorEmail)->send(new PurchaseOrderApproved($purchaseOrder));
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order approved.');
    }

    /**
     * Reject a pending purchase order (with reason).
     */
    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('approve', $purchaseOrder);
        if (! $purchaseOrder->canBeApproved()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only pending purchase orders can be rejected.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        // approved_by at approved_at ang ginagamit natin bilang "reviewed by / reviewed at"
        $purchaseOrder->update([
            'status'           => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'approved_by'      => Auth::id(),
            'approved_at'      => now(),
        ]);

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order rejected.');
    }

    /**
     * Mark an approved purchase order as sent to the supplier.
     */
    public function send(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('send', $purchaseOrder);
        if ($purchaseOrder->status !== 'approved') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only approved purchase orders can be sent.');
        }

        $purchaseOrder->update(['status' => 'sent']);

        // I-email ang PO sa supplier (instructor)
        $supplierEmail = $purchaseOrder->supplier?->email;
        if ($supplierEmail) {
            Mail::to($supplierEmail)->send(new PurchaseOrderSent($purchaseOrder));
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order marked as sent.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('receive', $purchaseOrder);
        if (! $purchaseOrder->canBeReceived()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only orders that were sent can receive items.');
        }

        $request->validate([
            'received'   => ['required', 'array'],
            'received.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($request, $purchaseOrder) {
                $purchaseOrder->load('items');
                $anyReceived = false;

                foreach ($purchaseOrder->items as $item) {
                    $qty = (float) ($request->input('received.' . $item->id) ?? 0);

                    if ($qty <= 0) {
                        continue;
                    }

                    if ($qty > $item->outstanding_quantity) {
                        throw new \InvalidArgumentException(
                            'Received amount is more than the outstanding amount for item #' . $item->id . '.'
                        );
                    }

                    GradeTransaction::create([
                        'grade_item_id'    => $item->grade_item_id,
                        'user_id'          => Auth::id(),
                        'type'             => 'increase',
                        'quantity'         => $qty,
                        'reference_number' => 'RCV-' . now()->format('ymdHis') . '-' . $item->id,
                        'notes'            => 'Received via ' . $purchaseOrder->reference_number,
                    ]);

                    $item->increment('quantity_received', $qty);
                    $anyReceived = true;
                }

                if (! $anyReceived) {
                    throw new \InvalidArgumentException('Please enter at least one received amount.');
                }

                $purchaseOrder->load('items');
                $allDone = $purchaseOrder->items->every(fn ($i) => $i->outstanding_quantity <= 0);

                if ($allDone) {
                    $purchaseOrder->update([
                        'status'      => 'received',
                        'received_at' => now(),
                    ]);
                }
            });
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Items received and grades updated.');
    }

    /**
     * Ipaalam sa mga admin at manager na may PO na naghihintay ng approval.
     */
    private function notifyApprovers(PurchaseOrder $order): void
    {
        $emails = User::whereIn('role', ['admin', 'manager'])->pluck('email')->all();

        if (! empty($emails)) {
            Mail::to($emails)->send(new PurchaseOrderCreated($order));
        }
    }
}
