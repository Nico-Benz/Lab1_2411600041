@extends('layouts.inventory-app')

@section('content')

@php
    $editing = isset($product);
@endphp

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $editing ? 'Edit Product' : 'Add New Product' }}</h2>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">← Back to Products</a>
</div>

<div class="card stat-card">
    <div class="card-body">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $editing ? route('products.update', $product) : route('products.store') }}" method="POST">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $product->name ?? '') }}" placeholder="e.g. Notebook 80 leaves (Spiral)" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku ?? '') }}" placeholder="e.g. PP-010" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <select name="brand_id" class="form-select">
                        <option value="">-- No Brand --</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id ?? '') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Brand not listed? <a href="{{ route('brands.create') }}">Add a new brand</a>.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Select Category --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Category not listed? <a href="{{ route('categories.create') }}">Add a new category</a>.</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $product->description ?? '') }}</textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Quantity in Stock</label>
                    <input type="number" min="0" step="1" name="quantity" class="form-control" value="{{ old('quantity', $product->quantity ?? 0) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Reorder Level</label>
                    <input type="number" min="0" step="1" name="reorder_level" class="form-control" value="{{ old('reorder_level', $product->reorder_level ?? 10) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit Price (₱)</label>
                    <input type="number" min="0" step="0.01" name="unit_price" class="form-control" value="{{ old('unit_price', $product->unit_price ?? 0) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <input type="text" name="supplier" class="form-control" value="{{ old('supplier', $product->supplier ?? '') }}">
                </div>
            </div>

            <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">
                {{ $editing ? 'Update Product' : 'Save Product' }}
            </button>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>

    </div>
</div>

@endsection
