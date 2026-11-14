@extends('emails.layout')

@section('title', 'Purchase Order Created')
@section('tag', 'New purchase order')

@section('content')
    <h2 style="margin-top:0;">Purchase order {{ $po->reference_number }} was created</h2>

    <p>
        A new purchase order for <strong>{{ $po->supplier->name }}</strong> was created by
        <strong>{{ $po->createdBy->name ?? 'the system' }}</strong>.
        @if ($po->is_auto_generated)
            It was generated automatically because some products reached their reorder point.
        @endif
        Status: <strong>{{ ucfirst($po->status) }}</strong>.
    </p>

    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
        <tr style="background-color:#231C35; color:#ffffff; text-align:left;">
            <th>Product</th><th style="text-align:right;">Qty</th><th style="text-align:right;">Unit price</th><th style="text-align:right;">Line total</th>
        </tr>
        @foreach ($po->items as $item)
            <tr style="border-bottom:1px solid #e5e1ec;">
                <td>{{ $item->product->name ?? '—' }}</td>
                <td style="text-align:right;">{{ $item->quantity }}</td>
                <td style="text-align:right;">PHP {{ number_format((float) $item->unit_price, 2) }}</td>
                <td style="text-align:right;">PHP {{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="3" style="text-align:right;"><strong>Total (with tax)</strong></td>
            <td style="text-align:right;"><strong>PHP {{ number_format((float) $po->total, 2) }}</strong></td>
        </tr>
    </table>

    <p style="margin-top:24px;">
        <a href="{{ route('purchase-orders.show', $po) }}" style="background-color:#E67E22; color:#ffffff; padding:10px 20px; border-radius:6px; text-decoration:none;">Open purchase order</a>
    </p>
@endsection
