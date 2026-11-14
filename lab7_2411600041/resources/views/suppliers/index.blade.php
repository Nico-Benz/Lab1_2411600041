@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Suppliers</h2>
    @can('admin')
        <a href="{{ route('suppliers.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ Add Supplier</a>
    @endcan
</div>

@php
    $statusOptions = ['active' => 'Active', 'inactive' => 'Inactive'];
    if (auth()->user()->isAdmin()) {
        $statusOptions['deleted'] = 'Deleted (admin)';
    }

    $filterFields = [
        ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'col' => 4, 'placeholder' => 'Company, contact or email'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'col' => 2, 'placeholder' => 'All', 'options' => $statusOptions],
        ['name' => 'sort', 'label' => 'Sort by', 'type' => 'select', 'col' => 3, 'placeholder' => 'Company name',
            'options' => ['contact_person' => 'Contact person', 'lead_time_days' => 'Lead time', 'payment_terms' => 'Payment terms', 'created_at' => 'Date added']],
        ['name' => 'direction', 'label' => 'Order', 'type' => 'select', 'col' => 2, 'placeholder' => 'Ascending', 'options' => ['desc' => 'Descending']],
    ];
@endphp
<x-filter-form :action="route('suppliers.index')" :fields="$filterFields" />

<div class="card stat-card">
    <div class="card-body"> 
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Lead Time</th>
                        <th>Terms</th>
                        <th>Min. Order</th>
                        <th class="text-center">All Orders</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="fw-bold">{{ $supplier->name }}</td>
                            <td>
                                {{ $supplier->contact_person }}
                                <div class="small text-muted">{{ $supplier->email }}</div>
                            </td>
                            <td>{{ $supplier->lead_time_days }} day(s)</td>
                            <td>{{ $supplier->payment_terms }}</td>
                            <td>₱{{ number_format((float) $supplier->minimum_order_amount, 2) }}</td>
                            <td class="text-center">{{ $supplier->purchase_orders_count }}</td>
                            <td>
                                @if ($supplier->trashed())
                                    <span class="badge bg-dark">Deleted</span>
                                @elseif ($supplier->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($supplier->trashed())
                                    <form action="{{ route('suppliers.restore', $supplier->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                    </form>
                                @else
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    @can('admin')
                                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier? Old purchase orders will keep its name.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $suppliers->links('pagination::bootstrap-5') }}
    </div>
</div>

@endsection
