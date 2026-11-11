@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Stock Movements</h2>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">← Back to Products</a>
</div>

<div class="card stat-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reference</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('M d, Y g:i A') }}</td>
                            <td>{{ $transaction->product->sku ?? '—' }}</td>
                            <td>
                                @if ($transaction->product)
                                    <a href="{{ route('products.show', $transaction->product) }}">{{ $transaction->product->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($transaction->isStockIn())
                                    <span class="badge bg-success">Stock In</span>
                                @else
                                    <span class="badge bg-danger">Stock Out</span>
                                @endif
                            </td>
                            <td>{{ $transaction->quantity }}</td>
                            <td>{{ $transaction->reference_number ?? '—' }}</td>
                            <td>{{ $transaction->user->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No stock movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
