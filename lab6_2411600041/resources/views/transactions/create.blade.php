@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Record Stock Movement</h2>
    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Back to list</a>
</div>

<div class="card stat-card">
    <div class="card-body">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('transactions.store') }}" method="POST" id="movementForm">
            @csrf

            <div class="mb-3">
                <label class="form-label">Product</label>
                <select name="product_id" id="productSelect" class="form-select" required>
                    <option value="">Select a product</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" data-quantity="{{ $p->quantity }}" data-reorder="{{ $p->reorder_level }}"
                            {{ old('product_id', $selectedProduct) == $p->id ? 'selected' : '' }}>
                            {{ $p->sku }} — {{ $p->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text" id="stockInfo"></div>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Transaction Type</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="transaction_type" id="typeIn" value="in" {{ old('transaction_type', 'in') === 'in' ? 'checked' : '' }}>
                    <label class="form-check-label" for="typeIn">Stock In</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="transaction_type" id="typeOut" value="out" {{ old('transaction_type') === 'out' ? 'checked' : '' }}>
                    <label class="form-check-label" for="typeOut">Stock Out</label>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" id="quantityLabel">Quantity to add</label>
                <input type="number" name="quantity" id="quantity" class="form-control" min="1" step="1" value="{{ old('quantity') }}" required>
                <div class="form-text" id="quantityHint"></div>
                <div class="form-text fw-semibold" id="preview"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Reference Number (optional)</label>
                <input type="text" name="reference_number" class="form-control" maxlength="100" value="{{ old('reference_number') }}" placeholder="e.g. PO-1001">
            </div>

            <div class="mb-3">
                <label class="form-label">Notes (optional)</label>
                <textarea name="notes" class="form-control" rows="3" maxlength="500">{{ old('notes') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Save Movement</button>
                <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Show current stock when a product is chosen, and set the max quantity for stock out
    const select = document.getElementById('productSelect');
    const qty = document.getElementById('quantity');
    const info = document.getElementById('stockInfo');
    const hint = document.getElementById('quantityHint');
    const preview = document.getElementById('preview');
    const label = document.getElementById('quantityLabel');

    function currentType() { return document.querySelector('input[name="transaction_type"]:checked').value; }

    function update() {
        const opt = select.options[select.selectedIndex];
        const stock = opt && opt.value ? parseInt(opt.dataset.quantity, 10) : null;
        const type = currentType();

        label.textContent = type === 'out' ? 'Quantity to remove' : 'Quantity to add';
        info.textContent = stock === null ? '' : 'Current stock: ' + stock;

        if (type === 'out' && stock !== null) {
            qty.max = stock;
            hint.textContent = 'Maximum: ' + stock + ' (stock cannot go below 0)';
        } else {
            qty.removeAttribute('max');
            hint.textContent = '';
        }

        const n = parseInt(qty.value, 10);
        preview.textContent = '';
        preview.className = 'form-text fw-semibold';
        if (stock !== null && n > 0) {
            const result = type === 'out' ? stock - n : stock + n;
            if (result < 0) {
                preview.textContent = 'Result would be ' + result + ', which is not allowed.';
                preview.classList.add('text-danger');
            } else {
                preview.textContent = 'New stock: ' + result;
                preview.classList.add('text-success');
            }
        }
    }

    select.addEventListener('change', update);
    qty.addEventListener('input', update);
    document.querySelectorAll('input[name="transaction_type"]').forEach(r => r.addEventListener('change', update));
    update();
</script>
@endsection
