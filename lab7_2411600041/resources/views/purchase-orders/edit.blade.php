@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Edit {{ $order->reference_number }}</h2>
    <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-outline-secondary">← Back to Order</a>
</div>

<div class="card stat-card">
    <div class="card-body">
        <form action="{{ route('purchase-orders.update', $order) }}" method="POST">
            @csrf
            @method('PUT')
            @include('purchase-orders._form')
            <button type="submit" name="action" value="draft" class="btn btn-outline-dark">Save Draft</button>
            <button type="submit" name="action" value="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Save &amp; Send for Approval</button>
        </form>
    </div>
</div>

@endsection
