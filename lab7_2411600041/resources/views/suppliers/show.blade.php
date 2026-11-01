@extends('layouts.grade-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $supplier->company_name }}</h2>
    <div class="d-flex gap-2">
        @can('admin')
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Details Card -->
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Supplier Details</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        @if ($supplier->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Contact</dt>
                    <dd class="col-sm-8">{{ $supplier->full_contact }}</dd>

                    <dt class="col-sm-4">Address</dt>
                    <dd class="col-sm-8">{{ $supplier->address ?? '—' }}</dd>

                    <dt class="col-sm-4">Payment Terms</dt>
                    <dd class="col-sm-8">{{ $supplier->payment_terms }}</dd>

                    <dt class="col-sm-4">Lead Time</dt>
                    <dd class="col-sm-8">{{ $supplier->lead_time_days }} days</dd>

                    <dt class="col-sm-4">Minimum Order</dt>
                    <dd class="col-sm-8">{{ number_format($supplier->minimum_order_amount, 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Performance Metrics</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Total Orders</dt>
                    <dd class="col-sm-6">{{ $metrics['total_orders'] }}</dd>

                    <dt class="col-sm-6">Received Orders</dt>
                    <dd class="col-sm-6">{{ $metrics['received_orders'] }}</dd>

                    <dt class="col-sm-6">On-time Delivery</dt>
                    <dd class="col-sm-6">
                        {{ $metrics['rating'] !== null ? $metrics['rating'] . '%' : 'No rating yet' }}
                    </dd>

                    <dt class="col-sm-6">Avg. Delivery Time</dt>
                    <dd class="col-sm-6">
                        {{ $metrics['avg_delivery_days'] !== null ? $metrics['avg_delivery_days'] . ' days' : '—' }}
                        <small class="text-muted">(quoted: {{ $metrics['quoted_days'] }} days)</small>
                    </dd>

                    <dt class="col-sm-6">Total Order Value</dt>
                    <dd class="col-sm-6">{{ number_format($metrics['total_value'], 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- Recent Purchase Orders -->
<div class="card stat-card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Recent Purchase Orders</h5>
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentOrders as $order)
                    <tr>
                        <td>{{ $order->reference_number }}</td>
                        <td>{{ ucfirst($order->status) }}</td>
                        <td>{{ $order->order_date->format('Y-m-d') }}</td>
                        <td>{{ number_format($order->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No purchase orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Products Supplied -->
<div class="card stat-card">
    <div class="card-body">
        <h5 class="card-title mb-3">Subjects Supplied</h5>
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Subject Name</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $item)
                    <tr>
                        <td>{{ $item->subject_code }}</td>
                        <td><a href="{{ route('grade-items.show', $item) }}">{{ $item->subject_name }}</a></td>
                        <td>
                            @if ($item->isPending())
                                <span class="badge" style="background-color: var(--theme-accent);">Pending</span>
                            @elseif ($item->isBelowThreshold())
                                <span class="badge bg-danger">{{ number_format($item->current_grade, 2) }}</span>
                            @else
                                <span class="badge bg-success">{{ number_format($item->current_grade, 2) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">No subjects linked to this supplier yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection