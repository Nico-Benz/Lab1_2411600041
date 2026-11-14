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

<!-- Filters (reusable filter-form component) -->
@php
    $filterFields = [
        ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'col' => 2, 'placeholder' => 'Product, SKU or reference'],
        ['name' => 'product_id', 'label' => 'Product', 'type' => 'select', 'col' => 3, 'placeholder' => 'All products',
            'options' => $products->mapWithKeys(fn ($p) => [$p->id => $p->sku . ' — ' . $p->name])->all()],
        ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'col' => 2, 'placeholder' => 'All types',
            'options' => ['in' => 'Stock In', 'out' => 'Stock Out', 'adjustment' => 'Adjustment']],
        ['name' => 'user_id', 'label' => 'Done by', 'type' => 'select', 'col' => 2, 'placeholder' => 'Everyone',
            'options' => $users->pluck('name', 'id')->all()],
        ['name' => 'from', 'label' => 'From', 'type' => 'date', 'col' => 2],
        ['name' => 'to', 'label' => 'To', 'type' => 'date', 'col' => 2],
    ];
@endphp
<x-filter-form :action="route('transactions.index')" :fields="$filterFields">
    <a href="{{ route('exports.download', ['report' => 'transactions', 'format' => 'csv'] + request()->query()) }}" class="btn btn-outline-secondary">CSV</a>
    <a href="{{ route('exports.download', ['report' => 'transactions', 'format' => 'pdf'] + request()->query()) }}" class="btn btn-outline-secondary">PDF</a>
</x-filter-form>

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