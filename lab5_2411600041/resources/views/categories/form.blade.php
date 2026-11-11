@extends('layouts.inventory-app')

@section('content')

@php
    $editing = isset($category);
@endphp

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $editing ? 'Edit Category' : 'Add New Category' }}</h2>
    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">← Back to Categories</a>
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

        <form action="{{ $editing ? route('categories.update', $category) : route('categories.store') }}" method="POST">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="mb-3">
                <label class="form-label">Category Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" value="{{ old('description', $category->description ?? '') }}" placeholder="e.g. Notebooks, bond paper, pads">
            </div>

            <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">
                {{ $editing ? 'Update Category' : 'Save Category' }}
            </button>
            <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>

    </div>
</div>

@endsection
