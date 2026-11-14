@extends('emails.layout')

@section('title', 'Purchase Order Approved')
@section('tag', 'Approval')

@section('content')
    <h2 style="margin-top:0;">Your purchase order was approved</h2>

    <p>
        Purchase order <strong>{{ $po->reference_number }}</strong> for <strong>{{ $po->supplier->name }}</strong>
        (total <strong>PHP {{ number_format((float) $po->total, 2) }}</strong>) was approved by
        <strong>{{ $po->approvedBy->name ?? 'a manager' }}</strong>
        @if ($po->approved_at) on {{ $po->approved_at->format('M d, Y g:i A') }}@endif.
    </p>

    <p>The next step is to mark the order as <strong>sent</strong> so the supplier receives it.</p>

    <p style="margin-top:24px;">
        <a href="{{ route('purchase-orders.show', $po) }}" style="background-color:#E67E22; color:#ffffff; padding:10px 20px; border-radius:6px; text-decoration:none;">Open purchase order</a>
    </p>
@endsection
