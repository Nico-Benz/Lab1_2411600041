<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $po->reference_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #231C35; }
        h1 { font-size: 22px; margin: 0; color: #231C35; }
        .muted { color: #6E5774; }
        .header { border-bottom: 3px solid #E67E22; padding-bottom: 10px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        .info td { padding: 3px 0; vertical-align: top; }
        .items th { background: #231C35; color: #ffffff; padding: 6px; text-align: left; }
        .items td { padding: 6px; border-bottom: 1px solid #ddd; }
        .right { text-align: right; }
        .totals td { padding: 4px 6px; }
        .status { display: inline-block; background: #E67E22; color: #ffffff; padding: 2px 8px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PURCHASE ORDER</h1>
        <div class="muted">Student Supplies Inventory</div>
    </div>

    <table class="info">
        <tr>
            <td width="55%">
                <strong>Supplier</strong><br>
                {{ $po->supplier->name }}<br>
                {{ $po->supplier->contact_person }}<br>
                {{ $po->supplier->email }} &middot; {{ $po->supplier->phone }}<br>
                {{ $po->supplier->address }}
            </td>
            <td>
                <strong>Reference:</strong> {{ $po->reference_number }}<br>
                <strong>Status:</strong> <span class="status">{{ strtoupper($po->status) }}</span><br>
                <strong>Order date:</strong> {{ $po->order_date->format('M d, Y') }}<br>
                <strong>Expected delivery:</strong> {{ $po->expected_delivery_date ? $po->expected_delivery_date->format('M d, Y') : '-' }}<br>
                <strong>Payment terms:</strong> {{ $po->supplier->payment_terms }}<br>
                <strong>Created by:</strong> {{ $po->createdBy->name ?? '-' }}
            </td>
        </tr>
    </table>

    <br>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>SKU</th>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="right">Unit price</th>
                <th class="right">Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($po->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product->sku ?? '-' }}</td>
                    <td>{{ $item->product->name ?? '-' }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">PHP {{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">PHP {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="width: 45%; margin-left: 55%; margin-top: 10px;">
        <tr><td>Subtotal</td><td class="right">PHP {{ number_format((float) $po->subtotal, 2) }}</td></tr>
        <tr><td>Tax ({{ rtrim(rtrim(number_format(config('procurement.tax_rate') * 100, 2), '0'), '.') }}%)</td><td class="right">PHP {{ number_format((float) $po->tax, 2) }}</td></tr>
        <tr><td><strong>Total</strong></td><td class="right"><strong>PHP {{ number_format((float) $po->total, 2) }}</strong></td></tr>
    </table>

    @if ($po->notes)
        <p><strong>Notes:</strong><br>{{ $po->notes }}</p>
    @endif
    @if ($po->terms)
        <p><strong>Terms:</strong><br>{{ $po->terms }}</p>
    @endif
</body>
</html>
