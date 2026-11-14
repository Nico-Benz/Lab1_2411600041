@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $supplier->name }}
        @if ($supplier->is_active)
            <span class="badge bg-success fs-6">Active</span>
        @else
            <span class="badge bg-secondary fs-6">Inactive</span>
        @endif
    </h2>
    <div class="d-flex gap-2">
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">← Back</a>
        @can('admin')
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('create', \App\Models\PurchaseOrder::class)
            <a href="{{ route('purchase-orders.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ New Purchase Order</a>
        @endcan
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Supplier details</h5>
                <div class="row mb-2"><div class="col-5 text-muted">Contact person</div><div class="col-7">{{ $supplier->contact_person }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Email</div><div class="col-7">{{ $supplier->email }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Phone</div><div class="col-7">{{ $supplier->phone }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Address</div><div class="col-7">{{ $supplier->address ?: '—' }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Payment terms</div><div class="col-7">{{ $supplier->payment_terms }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Lead time</div><div class="col-7">{{ $supplier->lead_time_days }} day(s)</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Minimum order</div><div class="col-7">₱{{ number_format((float) $supplier->minimum_order_amount, 2) }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Performance</h5>
                <div class="row text-center g-2">
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['total_orders'] }}</div>
                        <div class="small text-muted">Orders placed</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['delivered_orders'] }}</div>
                        <div class="small text-muted">Delivered</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['on_time_rate'] !== null ? $metrics['on_time_rate'] . '%' : '—' }}</div>
                        <div class="small text-muted">On time</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['accuracy_rate'] !== null ? $metrics['accuracy_rate'] . '%' : '—' }}</div>
                        <div class="small text-muted">Accuracy</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['avg_actual_days'] ?? '—' }}</div>
                        <div class="small text-muted">Avg days</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['avg_quoted_days'] ?? '—' }}</div>
                        <div class="small text-muted">Quoted days</div>
                    </div>
                </div>
                <div class="small text-muted mt-3">
                    Total value of placed orders: <strong>₱{{ number_format($metrics['total_value'], 2) }}</strong>.
                    "Avg days" is the real time from order to delivery; "Quoted days" is what the supplier promised.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Products supplied</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>SKU</th><th>Product</th><th class="text-end">Supplier price</th></tr></thead>
                        <tbody>
                            @forelse ($supplier->suppliedProducts as $product)
                                <tr>
                                    <td>{{ $product->sku }}</td>
                                    <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                                    <td class="text-end">₱{{ number_format((float) ($product->pivot->supplier_price ?? $product->unit_price), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">No products linked yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Recent purchase orders</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Reference</th><th>Date</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td><a href="{{ route('purchase-orders.show', $order) }}">{{ $order->reference_number }}</a></td>
                                    <td>{{ $order->order_date->format('M d, Y') }}</td>
                                    <td><span class="badge bg-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span></td>
                                    <td class="text-end">₱{{ number_format((float) $order->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No purchase orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card stat-card">
    <div class="card-body">
        <h5 class="card-title">Notes</h5>
        @include('partials.notes', ['noteable' => $supplier, 'noteableType' => 'supplier'])
    </div>
</div>

@endsection
