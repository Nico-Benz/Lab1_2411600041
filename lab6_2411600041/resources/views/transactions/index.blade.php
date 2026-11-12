@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Stock Movements</h2>
    <div class="d-flex gap-2">
        @can('admin')
            <a href="{{ route('adjustments.create') }}" class="btn btn-outline-secondary">Adjust Stock</a>
            <a href="{{ route('transactions.create') }}" class="btn" style="background-color: var(--theme-primary); color: white;">+ Record Movement</a>
        @endcan
    </div>
</div>

<!-- Filters -->
<div class="card stat-card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('transactions.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Product</label>
                <select name="product_id" class="form-select">
                    <option value="">All products</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->sku }} — {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Type</label>
                <select name="type" class="form-select">
                    <option value="">All types</option>
                    <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Stock In</option>
                    <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Stock Out</option>
                    <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" name="from" class="form-control" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" name="to" class="form-control" value="{{ request('to') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark">Filter</button>
                <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card stat-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Stock</th>
                        <th>Reference</th>
                        <th>By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('M d, Y g:i A') }}</td>
                            <td>
                                @if ($transaction->product)
                                    <a href="{{ route('products.show', $transaction->product) }}">{{ $transaction->product->name }}</a>
                                    <div class="small text-muted">{{ $transaction->product->sku }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="badge bg-{{ $transaction->type_color }}">{{ $transaction->type_label }}</span></td>
                            <td class="fw-bold {{ $transaction->signed_change >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $transaction->signed_change > 0 ? '+' : '' }}{{ $transaction->signed_change }}
                            </td>
                            <td class="small">{{ $transaction->quantity_before }} → {{ $transaction->quantity_after }}</td>
                            <td>{{ $transaction->reference_number ?? '—' }}</td>
                            <td>{{ $transaction->user->name ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @can('admin')
                                    <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this transaction and reverse its stock change?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No stock movements found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $transactions->links('pagination::bootstrap-5') }}
    </div>
</div>

@endsection