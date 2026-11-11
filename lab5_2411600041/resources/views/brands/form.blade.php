@extends('layouts.inventory-app')

@section('content')

@php
    $editing = isset($brand);
@endphp

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $editing ? 'Edit Brand' : 'Add New Brand' }}</h2>
    <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary">← Back to Brands</a>
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

        <form action="{{ $editing ? route('brands.update', $brand) : route('brands.store') }}" method="POST">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="mb-3">
                <label class="form-label">Brand Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $brand->name ?? '') }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" value="{{ old('description', $brand->description ?? '') }}" placeholder="e.g. Pens and school writing tools">
            </div>

            <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">
                {{ $editing ? 'Update Brand' : 'Save Brand' }}
            </button>
            <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>

    </div>
</div>

@endsection
