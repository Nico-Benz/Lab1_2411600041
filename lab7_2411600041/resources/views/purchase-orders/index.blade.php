@extends('layouts.grade-app')

@section('content')

@php
    $supplierOptions = $suppliers->mapWithKeys(fn ($s) => [
        $s->id => $s->company_name . ($s->trashed() ? ' (deleted)' : ''),
    ])->all();

    $statusOptions = collect($statuses)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all();

    $filterFields = [
        ['name' => 'search', 'label' => 'Reference', 'type' => 'text', 'col' => 2, 'placeholder' => 'e.g. 0004'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'col' => 2, 'options' => $statusOptions],
        ['name' => 'supplier_id', 'label' => 'Supplier (Instructor)', 'type' => 'select', 'col' => 2, 'options' => $supplierOptions],
        ['name' => 'date_from', 'label' => 'From', 'type' => 'date', 'col' => 2],
        ['name' => 'date_to', 'label' => 'To', 'type' => 'date', 'col' => 2],
    ];
@endphp

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Purchase Orders</h2>
    @can('create', App\Models\PurchaseOrder::class)
        <a href="{{ route('purchase-orders.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ New Purchase Order</a>
    @endcan
</div>

<!-- Filters -->
<x-filter-form :action="route('purchase-orders.index')" :fields="$filterFields" />

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