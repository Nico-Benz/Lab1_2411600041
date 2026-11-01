@extends('layouts.grade-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Suppliers</h2>
    @can('admin')
        <a href="{{ route('suppliers.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ Add Supplier</a>
    @endcan
</div>

<!-- Search, Filter and Sort -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('suppliers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, contact person, or email">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Sort by</label>
                <select name="sort" class="form-select">
                    <option value="company_name" @selected(request('sort', 'company_name') === 'company_name')>Company name</option>
                    <option value="lead_time_days" @selected(request('sort') === 'lead_time_days')>Lead time</option>
                    <option value="created_at" @selected(request('sort') === 'created_at')>Date added</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Order</label>
                <select name="direction" class="form-select">
                    <option value="asc" @selected(request('direction', 'asc') === 'asc')>Ascending</option>
                    <option value="desc" @selected(request('direction') === 'desc')>Descending</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn flex-grow-1" style="background-color: var(--theme-primary); color: white;">Apply</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Suppliers Table -->
<div class="card stat-card">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact Person</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Lead Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->company_name }}</td>
                        <td>{{ $supplier->contact_person }}</td>
                        <td>{{ $supplier->email }}</td>
                        <td>{{ $supplier->phone ?? '—' }}</td>
                        <td>{{ $supplier->lead_time_days }} days</td>
                        <td>
                            @if ($supplier->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('admin')
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No suppliers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $suppliers->links('pagination::bootstrap-5') }}
    </div>
</div>

@endsection