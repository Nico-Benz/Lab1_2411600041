@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Brand Management</h2>
    @can('admin')
    <a href="{{ route('brands.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ Add Brand</a>
    @endcan
</div>

<div class="card stat-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Brand Name</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold">{{ $brand->name }}</td>
                            <td>{{ $brand->description ?? '—' }}</td>
                            <td><span class="badge bg-secondary">{{ $brand->products_count }}</span></td>
                            <td class="text-nowrap">
                                <a href="{{ route('brands.show', $brand) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @can('admin')
                                <a href="{{ route('brands.edit', $brand) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('brands.destroy', $brand) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this brand?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No brands yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
