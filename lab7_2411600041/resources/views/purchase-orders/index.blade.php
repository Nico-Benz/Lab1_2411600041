@extends('layouts.grade-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Purchase Orders</h2>
    @can('create', App\Models\PurchaseOrder::class)
        <a href="{{ route('purchase-orders.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ New Purchase Order</a>
    @endcan
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('purchase-orders.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Supplier (Instructor)</label>
                <select name="supplier_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>
                            {{ $supplier->company_name }}{{ $supplier->trashed() ? ' (deleted)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn flex-grow-1" style="background-color: var(--theme-primary); color: white;">Apply</button>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Purchase Orders Table -->
<div class="card stat-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            $badge = match ($order->status) {
                                'draft'     => 'bg-secondary',
                                'pending'   => 'bg-warning text-dark',
                                'approved'  => 'bg-info text-dark',
                                'rejected'  => 'bg-danger',
                                'sent'      => 'bg-primary',
                                'received'  => 'bg-success',
                                'cancelled' => 'bg-dark',
                                default     => 'bg-secondary',
                            };
                        @endphp
                        <tr>
                            <td>{{ $order->reference_number }}</td>
                            <td>{{ $order->supplier->company_name ?? '—' }}</td>
                            <td>{{ $order->order_date?->format('M d, Y') }}</td>
                            <td>₱{{ number_format($order->total, 2) }}</td>
                            <td><span class="badge {{ $badge }}">{{ ucfirst($order->status) }}</span></td>
                            <td>{{ $order->createdBy->name ?? '—' }}</td>
                            <td>
                                <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No purchase orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</div>

@endsection