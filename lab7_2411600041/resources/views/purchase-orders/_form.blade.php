{{--
    Shared by purchase-orders/create and purchase-orders/edit.
    $suppliers, $productOptions and (when editing) $order.
    public/js/purchase-order-form.js builds the line item rows.
--}}
@php
    $existingLines = isset($order)
        ? $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => (float) $item->unit_price,
        ])->values()->all()
        : [];

    $lines = old('items', $existingLines);
    $lines = is_array($lines) ? array_values($lines) : [];
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label">Supplier</label>
        <select name="supplier_id" id="supplier_id" class="form-select" required>
            <option value="">— Choose supplier —</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" data-lead="{{ $supplier->lead_time_days }}"
                    {{ (string) old('supplier_id', $order->supplier_id ?? '') === (string) $supplier->id ? 'selected' : '' }}>
                    {{ $supplier->name }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Only active suppliers are listed.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Order Date</label>
        <input type="date" name="order_date" id="order_date" class="form-control" value="{{ old('order_date', isset($order) ? $order->order_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Expected Delivery</label>
        <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date', isset($order) && $order->expected_delivery_date ? $order->expected_delivery_date->format('Y-m-d') : '') }}">
        <div class="form-text" id="leadHint">Filled automatically from the supplier lead time.</div>
    </div>
</div>

<h5 class="mt-4">Line items</h5>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th style="width: 42%;">Product</th>
                <th style="width: 14%;">Quantity</th>
                <th style="width: 18%;">Unit Price (₱)</th>
                <th class="text-end" style="width: 18%;">Line Total</th>
                <th style="width: 8%;"></th>
            </tr>
        </thead>
        <tbody id="itemsBody"></tbody>
    </table>
</div>
<button type="button" id="addLine" class="btn btn-outline-secondary btn-sm mb-3">+ Add line</button>

<div class="row justify-content-end mb-3">
    <div class="col-md-4">
        <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="subtotalText">₱0.00</strong></div>
        <div class="d-flex justify-content-between"><span>Tax ({{ rtrim(rtrim(number_format(config('procurement.tax_rate') * 100, 2), '0'), '.') }}%)</span><strong id="taxText">₱0.00</strong></div>
        <div class="d-flex justify-content-between fs-5"><span>Total</span><strong id="totalText">₱0.00</strong></div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $order->notes ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Terms</label>
        <textarea name="terms" class="form-control" rows="3">{{ old('terms', $order->terms ?? config('procurement.default_terms')) }}</textarea>
    </div>
</div>

<script>
    window.poConfig = {
        products: @json($productOptions),
        items: @json($lines),
        taxRate: {{ (float) config('procurement.tax_rate') }}
    };
</script>
<script src="{{ asset('js/purchase-order-form.js') }}"></script>
