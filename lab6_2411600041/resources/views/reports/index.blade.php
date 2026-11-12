@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Inventory Reports</h2>
</div>

<!-- 1. Stock Movement Report -->
<div class="card stat-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Stock Movement Report</h5>
        <a href="{{ route('reports.export', ['type' => 'movements', 'from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-secondary">Export CSV</a>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-md-3"><label class="form-label small">From</label><input type="date" name="from" class="form-control" value="{{ $from }}"></div>
            <div class="col-md-3"><label class="form-label small">To</label><input type="date" name="to" class="form-control" value="{{ $to }}"></div>
            <div class="col-md-3"><button type="submit" class="btn btn-dark">Generate</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Change</th><th>Balance after</th><th>Reference</th><th>By</th></tr></thead>
                <tbody>
                    @forelse ($movements as $t)
                        <tr>
                            <td>{{ $t->created_at->format('M d, Y g:i A') }}</td>
                            <td>{{ $t->product->name ?? '—' }}</td>
                            <td><span class="badge bg-{{ $t->type_color }}">{{ $t->type_label }}</span></td>
                            <td class="{{ $t->signed_change >= 0 ? 'text-success' : 'text-danger' }}">{{ $t->signed_change > 0 ? '+' : '' }}{{ $t->signed_change }}</td>
                            <td>{{ $t->quantity_after }}</td>
                            <td>{{ $t->reference_number ?? '—' }}</td>
                            <td>{{ $t->user->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No movements in this date range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 2. Low Stock Report -->
<div class="card stat-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Low Stock Report</h5>
        <a href="{{ route('reports.export', ['type' => 'low-stock']) }}" class="btn btn-sm btn-outline-secondary">Export CSV</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Qty</th><th>Reorder level</th><th>Supplier</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($lowStock as $p)
                        <tr>
                            <td>{{ $p->sku }}</td>
                            <td><a href="{{ route('products.show', $p) }}">{{ $p->name }}</a></td>
                            <td>{{ $p->category->name ?? '—' }}</td>
                            <td>{{ $p->quantity }}</td>
                            <td>{{ $p->reorder_level }}</td>
                            <td>{{ $p->supplier ?? '—' }}</td>
                            <td><span class="badge bg-{{ $p->stock_status_color }}">{{ $p->stock_status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">All products are well stocked.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 3. Inventory Valuation Report -->
<div class="card stat-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Inventory Valuation Report</h5>
        <a href="{{ route('reports.export', ['type' => 'valuation']) }}" class="btn btn-sm btn-outline-secondary">Export CSV</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead><tr><th>SKU</th><th>Product</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Total value</th></tr></thead>
                <tbody>
                    @foreach ($products as $p)
                        <tr>
                            <td>{{ $p->sku }}</td>
                            <td>{{ $p->name }}</td>
                            <td class="text-end">{{ $p->quantity }}</td>
                            <td class="text-end text-money">₱{{ number_format($p->unit_price, 2) }}</td>
                            <td class="text-end text-money">₱{{ number_format($p->quantity * $p->unit_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold"><td colspan="4" class="text-end">Total inventory value</td><td class="text-end text-money">₱{{ number_format($totalValue, 2) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endsection
