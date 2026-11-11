@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between flex-wrap align-items-center pb-2 mb-3 border-bottom">
    <h2 id="greeting">{{ $greeting }}, {{ Auth::user()->name }}!</h2>
    <a href="{{ route('products.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ Add Product</a>
</div>

<!-- Widgets -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Products</h5>
                <h2 class="card-text fw-bold" style="color: var(--theme-primary);">{{ $totalProducts }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Low Stock</h5>
                <h2 class="card-text fw-bold" style="color: var(--theme-accent);">{{ $lowStockCount }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Out of Stock</h5>
                <h2 class="card-text fw-bold text-danger">{{ $outOfStockCount }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Inventory Value</h5>
                <h2 class="card-text fw-bold text-success">₱{{ number_format($inventoryValue, 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Recent Products -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Products</h5>
                <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body">
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @forelse ($recentProducts as $product)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $product->name }}</div>
                                    <div class="small text-muted">{{ $product->sku }} · {{ $product->category->name ?? '—' }}</div>
                                </td>
                                <td class="text-end"><span class="badge bg-secondary">{{ $product->quantity }} pcs</span></td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted">No products yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header" style="background-color: #FFF3CD;">
                <h5 class="mb-0">Low Stock Alerts</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @forelse ($lowStockProducts as $product)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $product->name }}</div>
                                    <div class="small text-muted">Reorder at {{ $product->reorder_level }}</div>
                                </td>
                                <td class="text-end">
                                    @if ($product->isOutOfStock())
                                        <span class="badge bg-danger">Out of stock</span>
                                    @else
                                        <span class="badge" style="background-color: var(--theme-accent);">{{ $product->quantity }} left</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted">All products are well stocked.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Brands -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Brands</h5>
                <span class="badge bg-success">{{ $brands->count() }} total</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr><th>Brand Name</th><th>Description</th><th class="text-end">Products</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($brands as $brand)
                                <tr>
                                    <td>{{ $brand->name }}</td>
                                    <td>{{ $brand->description ?? '—' }}</td>
                                    <td class="text-end"><span class="badge bg-secondary">{{ $brand->products_count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">No brands yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Categories</h5>
                <span class="badge bg-success">{{ $categories->count() }} total</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr><th>Category Name</th><th>Description</th><th class="text-end">Products</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $category->description ?? '—' }}</td>
                                    <td class="text-end"><span class="badge bg-secondary">{{ $category->products_count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">No categories yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Stock Movements -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Stock Movements</h5>
        <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reference</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentTransactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('M d, Y g:i A') }}</td>
                            <td>{{ $transaction->product->name ?? '—' }}</td>
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
                        <tr><td colspan="6" class="text-center text-muted">No stock movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
