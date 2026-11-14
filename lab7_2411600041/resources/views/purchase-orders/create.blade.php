@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>New Purchase Order</h2>
    <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">← Back to Purchase Orders</a>
</div>

<div class="card stat-card">
    <div class="card-body">
        <form action="{{ route('purchase-orders.store') }}" method="POST">
            @csrf
            @include('purchase-orders._form')
            <button type="submit" name="action" value="draft" class="btn btn-outline-dark">Save as Draft</button>
            <button type="submit" name="action" value="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Save &amp; Send for Approval</button>
        </form>
    </div>
</div>

@endsection
