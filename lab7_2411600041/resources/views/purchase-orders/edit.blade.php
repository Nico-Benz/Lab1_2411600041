@extends('layouts.grade-app')

@section('content')

@php
    // Kung may validation error, gamitin ang old input. Kung wala, gamitin ang kasalukuyang items ng PO.
    $initialItems = old('items', $order->items->map(fn ($i) => [
        'grade_item_id'    => $i->grade_item_id,
        'quantity_ordered' => $i->quantity_ordered,
        'unit_price'       => $i->unit_price,
    ])->values()->all());
@endphp

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Edit {{ $order->reference_number }}</h2>
    <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card stat-card">
    <div class="card-body">
        <form method="POST" action="{{ route('purchase-orders.update', $order) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Supplier (Instructor)</label>
                    <select name="supplier_id" id="supplierSelect" class="form-select @error('supplier_id') is-invalid @enderror">
                        <option value="">-- Select supplier --</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $order->supplier_id) === (string) $supplier->id)>{{ $supplier->company_name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Order Date</label>
                    <input type="date" name="order_date" value="{{ old('order_date', $order->order_date?->toDateString()) }}" class="form-control @error('order_date') is-invalid @enderror">
                    @error('order_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Expected Delivery</label>
                    <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date', $order->expected_delivery_date?->toDateString()) }}" class="form-control @error('expected_delivery_date') is-invalid @enderror">
                    @error('expected_delivery_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr class="my-4">

            <h5>Line Items</h5>
            <p class="text-muted small" id="itemHint">Select a supplier first. Only that instructor's subjects will show.</p>

            <div id="itemRows"></div>

            <button type="button" id="addRowBtn" class="btn btn-outline-primary btn-sm" disabled>+ Add Line Item</button>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tax (amount in ₱)</label>
                    <input type="number" step="0.01" min="0" name="tax" id="taxInput" value="{{ old('tax', $order->tax) }}" class="form-control @error('tax') is-invalid @enderror">
                    @error('tax')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subtotal</label>
                    <input type="text" id="subtotalText" class="form-control" value="₱0.00" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Total (preview)</label>
                    <input type="text" id="totalText" class="form-control fw-bold" value="₱0.00" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $order->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Terms</label>
                    <textarea name="terms" rows="2" class="form-control @error('terms') is-invalid @enderror">{{ old('terms', $order->terms) }}</textarea>
                    @error('terms')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                <button type="submit" name="action" value="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Submit for Approval</button>
                <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const products = @json($products);
    const oldItems = @json($initialItems);

    const supplierSelect = document.getElementById('supplierSelect');
    const rowsBox = document.getElementById('itemRows');
    const addBtn = document.getElementById('addRowBtn');
    const taxInput = document.getElementById('taxInput');
    const hint = document.getElementById('itemHint');
    let rowIndex = 0;

    function productsForSupplier() {
        return products.filter(p => String(p.supplier_id) === String(supplierSelect.value));
    }

    function addRow(data = {}) {
        if (!supplierSelect.value) return;

        const i = rowIndex++;
        const options = productsForSupplier().map(p =>
            `<option value="${p.id}" ${String(data.grade_item_id) === String(p.id) ? 'selected' : ''}>${p.subject_code} - ${p.subject_name}</option>`
        ).join('');

        const div = document.createElement('div');
        div.className = 'row g-2 align-items-end mb-3 item-row';
        div.innerHTML = `
            <div class="col-12 col-md-5">
                <label class="form-label small">Subject</label>
                <select name="items[${i}][grade_item_id]" class="form-select">${options}</select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Points to recover</label>
                <input type="number" step="0.01" min="0.01" name="items[${i}][quantity_ordered]" value="${data.quantity_ordered ?? ''}" class="form-control qty-input">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Fee per point (₱)</label>
                <input type="number" step="0.01" min="0" name="items[${i}][unit_price]" value="${data.unit_price ?? ''}" class="form-control price-input">
            </div>
            <div class="col-8 col-md-2">
                <label class="form-label small">Line total</label>
                <input type="text" class="form-control line-total" value="₱0.00" readonly>
            </div>
            <div class="col-4 col-md-1">
                <button type="button" class="btn btn-outline-danger w-100 remove-row">X</button>
            </div>
        `;
        rowsBox.appendChild(div);
        recalc();
    }

    function recalc() {
        let subtotal = 0;
        rowsBox.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const line = qty * price;
            row.querySelector('.line-total').value = '₱' + line.toFixed(2);
            subtotal += line;
        });
        const tax = parseFloat(taxInput.value) || 0;
        document.getElementById('subtotalText').value = '₱' + subtotal.toFixed(2);
        document.getElementById('totalText').value = '₱' + (subtotal + tax).toFixed(2);
    }

    function onSupplierChange(useOld) {
        rowsBox.innerHTML = '';
        const list = productsForSupplier();

        if (!supplierSelect.value) {
            addBtn.disabled = true;
            hint.textContent = "Select a supplier first. Only that instructor's subjects will show.";
            recalc();
            return;
        }

        if (list.length === 0) {
            addBtn.disabled = true;
            hint.textContent = 'This instructor has no subjects linked yet. Link a subject first (Subjects > Edit).';
            recalc();
            return;
        }

        addBtn.disabled = false;
        hint.textContent = 'Subjects of the selected instructor:';

        const oldList = Object.values(oldItems || {});
        if (useOld && oldList.length > 0) {
            oldList.forEach(item => addRow(item));
        } else {
            addRow();
        }
    }

    supplierSelect.addEventListener('change', () => onSupplierChange(false));
    addBtn.addEventListener('click', () => addRow());
    taxInput.addEventListener('input', recalc);
    rowsBox.addEventListener('input', recalc);
    rowsBox.addEventListener('click', e => {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.item-row').remove();
            recalc();
        }
    });

    onSupplierChange(true);
</script>
@endsection