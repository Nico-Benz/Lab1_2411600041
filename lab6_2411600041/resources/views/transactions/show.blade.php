@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Transaction #{{ $transaction->id }}</h2>
    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Back to list</a>
</div>

<div class="row">
    <div class="col-md-7 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title text-muted mb-3">Transaction Details</h5>
                <div class="row mb-2"><div class="col-4 text-muted">Type</div><div class="col-8"><span class="badge bg-{{ $transaction->type_color }}">{{ $transaction->type_label }}</span></div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Change</div><div class="col-8 fw-bold {{ $transaction->signed_change >= 0 ? 'text-success' : 'text-danger' }}">{{ $transaction->signed_change > 0 ? '+' : '' }}{{ $transaction->signed_change }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Stock before</div><div class="col-8">{{ $transaction->quantity_before }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Stock after</div><div class="col-8">{{ $transaction->quantity_after }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Reference</div><div class="col-8">{{ $transaction->reference_number ?? '—' }}</div></div>
                @if ($transaction->reason)
                    <div class="row mb-2"><div class="col-4 text-muted">Reason</div><div class="col-8">{{ $transaction->reason }}</div></div>
                @endif
                <div class="row mb-2"><div class="col-4 text-muted">Notes</div><div class="col-8">{{ $transaction->notes ?? '—' }}</div></div>
                <div class="row"><div class="col-4 text-muted">Recorded on</div><div class="col-8">{{ $transaction->created_at->format('M d, Y g:i A') }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card stat-card mb-3">
            <div class="card-body">
                <h5 class="card-title text-muted mb-3">Product</h5>
                @if ($transaction->product)
                    <div class="row mb-2"><div class="col-4 text-muted">SKU</div><div class="col-8"><a href="{{ route('products.show', $transaction->product) }}">{{ $transaction->product->sku }}</a></div></div>
                    <div class="row mb-2"><div class="col-4 text-muted">Name</div><div class="col-8">{{ $transaction->product->name }}</div></div>
                    <div class="row mb-2"><div class="col-4 text-muted">Category</div><div class="col-8">{{ $transaction->product->category->name ?? '—' }}</div></div>
                    <div class="row mb-2"><div class="col-4 text-muted">Stock now</div><div class="col-8">{{ $transaction->product->quantity }} <span class="badge bg-{{ $transaction->product->stock_status_color }}">{{ $transaction->product->stock_status }}</span></div></div>
                    <div class="row"><div class="col-4 text-muted">Reorder at</div><div class="col-8">{{ $transaction->product->reorder_level }}</div></div>
                @endif
            </div>
        </div>
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title text-muted mb-3">Recorded By</h5>
                <div class="row mb-2"><div class="col-4 text-muted">Name</div><div class="col-8">{{ $transaction->user->name ?? '—' }}</div></div>
                <div class="row"><div class="col-4 text-muted">Email</div><div class="col-8">{{ $transaction->user->email ?? '—' }}</div></div>
            </div>
        </div>
    </div>
</div>

@can('admin')
    <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="mt-3" onsubmit="return confirm('Delete this transaction and reverse its stock change?');">
        @csrf
        @method('DELETE')
        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Back to list</a>
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
@endcan

@endsection
