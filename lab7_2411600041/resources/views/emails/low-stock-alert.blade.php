@extends('emails.layout')

@section('title', 'Low Stock Alert')
@section('tag', 'Low stock alert')

@section('content')
    <h2 style="margin-top:0;">{{ $product->name }} is running low</h2>

    <table cellpadding="6" cellspacing="0" style="font-size:14px;">
        <tr><td style="color:#6E5774;">SKU</td><td><strong>{{ $product->sku }}</strong></td></tr>
        <tr><td style="color:#6E5774;">Current quantity</td><td><strong style="color:#D9534F;">{{ $product->quantity }}</strong></td></tr>
        <tr><td style="color:#6E5774;">Reorder level</td><td>{{ $product->reorder_level }}</td></tr>
        <tr><td style="color:#6E5774;">Supplier</td><td>{{ $product->supplier->name ?? 'No supplier yet' }}</td></tr>
        @if ($product->supplier)
            <tr><td style="color:#6E5774;">Lead time</td><td>{{ $product->supplier->lead_time_days }} day(s)</td></tr>
        @endif
        @if ($suggestedQuantity > 0)
            <tr><td style="color:#6E5774;">Suggested order</td><td><strong>{{ $suggestedQuantity }}</strong> pcs</td></tr>
        @endif
    </table>

    <p style="margin-top:24px;">
        <a href="{{ route('products.show', $product) }}" style="background-color:#E67E22; color:#ffffff; padding:10px 20px; border-radius:6px; text-decoration:none;">View product</a>
    </p>
@endsection
