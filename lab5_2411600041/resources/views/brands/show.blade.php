@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $brand->name }}</h2>
    <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary">← Back to Brands</a>
</div>

<div class="card stat-card mb-4">
    <div class="card-body">
        <div class="row mb-2">
            <div class="col-3 text-muted">Brand Name</div>
            <div class="col-9">{{ $brand->name }}</div>
        </div>
        <div class="row mb-2">
            <div class="col-3 text-muted">Description</div>
            <div class="col-9">{{ $brand->description ?? '—' }}</div>
        </div>
        <div class="row">
            <div class="col-3 text-muted">Products</div>
            <div class="col-9">{{ $products->count() }}</div>
        </div>
    </div>
</div>

<div class="card stat-card">
    <div class="card-header"><h5 class="mb-0">Products under this brand</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                            <td>{{ $product->category->name ?? '—' }}</td>
                            <td>{{ $product->quantity }}</td>
                            <td>₱{{ number_format($product->unit_price, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
