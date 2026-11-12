@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>School Supplies Inventory</h2>
    @can('admin')
    <a href="{{ route('products.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ Add Product</a>
    @endcan
</div>

<!-- Filter Controls -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Category</label>
                <select id="categoryFilter" class="form-select">
                    <option value="all">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->name }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Brand</label>
                <select id="brandFilter" class="form-select">
                    <option value="all">All Brands</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->name }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Stock Status</label>
                <select id="statusFilter" class="form-select">
                    <option value="all">All Status</option>
                    <option value="In Stock">In Stock</option>
                    <option value="Low Stock">Low Stock</option>
                    <option value="Out of Stock">Out of Stock</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button id="clearFilters" class="btn btn-outline-secondary w-100">Clear Filters</button>
            </div>
        </div>
    </div>
</div>

<div class="mb-2">
    <input type="text" id="searchBox" class="form-control" placeholder="Search product name, SKU, or brand...">
</div>

<!-- Products Table -->
<div class="card stat-card">
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-hover align-middle" id="productsTable">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr
                        class="{{ $product->isOutOfStock() || $product->isLowStock() ? 'low-grade-row' : '' }}"
                        data-category="{{ $product->category->name ?? '' }}"
                        data-brand="{{ $product->brand->name ?? '' }}"
                        data-status="{{ $product->isOutOfStock() ? 'Out of Stock' : ($product->isLowStock() ? 'Low Stock' : 'In Stock') }}">
                        <td>{{ $product->sku }}</td>
                        <td>
                            <div class="fw-bold">{{ $product->name }}</div>
                            <div class="small text-muted">{{ $product->supplier ?? '' }}</div>
                        </td>
                        <td>
                            @if ($product->brand)
                                <span class="badge bg-secondary">{{ $product->brand->name }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td><span class="badge bg-info text-dark">{{ $product->category->name ?? '—' }}</span></td>
                        <td>{{ $product->quantity }}</td>
                        <td>₱{{ number_format($product->unit_price, 2) }}</td>
                        <td>
                            @if ($product->isOutOfStock())
                                <span class="badge bg-danger">Out of Stock</span>
                            @elseif ($product->isLowStock())
                                <span class="badge" style="background-color: var(--theme-accent);">Low Stock</span>
                            @else
                                <span class="badge bg-success">In Stock</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('admin')
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function applyFilters() {
        const cat = document.getElementById('categoryFilter').value;
        const brand = document.getElementById('brandFilter').value;
        const status = document.getElementById('statusFilter').value;
        const search = document.getElementById('searchBox').value.toLowerCase();

        document.querySelectorAll('#productsTable tbody tr').forEach(row => {
            if (!row.dataset.category && !row.dataset.status) return;
            const matches = (cat === 'all' || row.dataset.category === cat)
                && (brand === 'all' || row.dataset.brand === brand)
                && (status === 'all' || row.dataset.status === status)
                && row.textContent.toLowerCase().includes(search);
            row.style.display = matches ? '' : 'none';
        });
    }

    document.getElementById('categoryFilter').addEventListener('change', applyFilters);
    document.getElementById('brandFilter').addEventListener('change', applyFilters);
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    document.getElementById('searchBox').addEventListener('input', applyFilters);
    document.getElementById('clearFilters').addEventListener('click', function () {
        document.getElementById('categoryFilter').value = 'all';
        document.getElementById('brandFilter').value = 'all';
        document.getElementById('statusFilter').value = 'all';
        document.getElementById('searchBox').value = '';
        applyFilters();
    });
</script>
@endsection
