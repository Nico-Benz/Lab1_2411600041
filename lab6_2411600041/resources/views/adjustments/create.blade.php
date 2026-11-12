@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Adjust Stock</h2>
    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Back to list</a>
</div>

<div class="card stat-card">
    <div class="card-body">
        <p class="text-muted">Use this when the system quantity does not match the physical count (recount, damaged goods, loss, encoding error). The system records the difference as an <strong>Adjustment</strong>, so the old and new quantity stay in the history.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('adjustments.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label">Product</label>
                <select name="product_id" id="productSelect" class="form-select" required>
                    <option value="">Select a product</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" data-quantity="{{ $p->quantity }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->sku }} — {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Quantity in the system</label>
                    <input type="text" id="systemQty" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Physical count</label>
                    <input type="number" name="physical_count" id="physicalCount" class="form-control" min="0" step="1" value="{{ old('physical_count') }}" required>
                    <div class="form-text fw-semibold" id="difference"></div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Reason for adjustment</label>
                <select name="reason" class="form-select" required>
                    <option value="">Select a reason</option>
                    @foreach ($reasons as $reason)
                        <option value="{{ $reason }}" {{ old('reason') === $reason ? 'selected' : '' }}>{{ $reason }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes (optional)</label>
                <textarea name="notes" class="form-control" rows="3" maxlength="500">{{ old('notes') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Save Adjustment</button>
                <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const select = document.getElementById('productSelect');
    const systemQty = document.getElementById('systemQty');
    const physical = document.getElementById('physicalCount');
    const diff = document.getElementById('difference');

    // Load the current system quantity by AJAX (so it is always up to date)
    async function loadStock() {
        diff.textContent = '';
        if (!select.value) { systemQty.value = ''; return; }
        try {
            const res = await fetch("{{ url('/api/products') }}/" + select.value + "/stock", { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            const data = await res.json();
            systemQty.value = data.quantity;
        } catch (e) {
            systemQty.value = select.options[select.selectedIndex].dataset.quantity; // fallback
        }
        showDiff();
    }

    function showDiff() {
        const sys = parseInt(systemQty.value, 10);
        const phys = parseInt(physical.value, 10);
        diff.className = 'form-text fw-semibold';
        if (isNaN(sys) || isNaN(phys)) { diff.textContent = ''; return; }
        const d = phys - sys;
        if (d === 0) { diff.textContent = 'No difference. The quantity is already ' + sys + '.'; diff.classList.add('text-muted'); }
        else { diff.textContent = 'Difference: ' + (d > 0 ? '+' : '') + d; diff.classList.add(d > 0 ? 'text-success' : 'text-danger'); }
    }

    select.addEventListener('change', loadStock);
    physical.addEventListener('input', showDiff);
    loadStock();
</script>
@endsection
