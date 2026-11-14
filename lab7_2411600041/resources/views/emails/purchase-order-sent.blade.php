@extends('emails.layout')

@section('title', 'New Purchase Order')
@section('tag', 'Purchase order for supplier')

@section('content')
    <h2 style="margin-top:0;">New purchase order {{ $po->reference_number }}</h2>

    <p>Hello {{ $po->supplier->contact_person }},</p>
    <p>
        Please see the attached purchase order. We would like to receive the items below on or before
        <strong>{{ $po->expected_delivery_date ? $po->expected_delivery_date->format('M d, Y') : 'the agreed date' }}</strong>.
    </p>

    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
        <tr style="background-color:#231C35; color:#ffffff; text-align:left;">
            <th>Product</th><th style="text-align:right;">Qty</th><th style="text-align:right;">Unit price</th>
        </tr>
        @foreach ($po->items as $item)
            <tr style="border-bottom:1px solid #e5e1ec;">
                <td>{{ $item->product->name ?? '—' }}</td>
                <td style="text-align:right;">{{ $item->quantity }}</td>
                <td style="text-align:right;">PHP {{ number_format((float) $item->unit_price, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <p>Payment terms: <strong>{{ $po->supplier->payment_terms }}</strong>. Thank you!</p>
@endsection
