@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Edit Supplier</h2>
    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-outline-secondary">← Back to Supplier</a>
</div>

<div class="card stat-card">
    <div class="card-body">
        <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
            @csrf
            @method('PUT')
            @include('suppliers._form')
            <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Update Supplier</button>
        </form>
    </div>
</div>

@endsection
