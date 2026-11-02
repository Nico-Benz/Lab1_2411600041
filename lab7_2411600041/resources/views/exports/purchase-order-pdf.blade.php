<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $order->reference_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #231C35; }
        h1 { font-size: 18px; margin: 0 0 4px 0; }
        .muted { color: #6E5774; }
        .info { margin: 12px 0; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #231C35; color: #ffffff; text-align: left; padding: 6px; }
        td { padding: 6px; border-bottom: 1px solid #dddddd; }
        .right { text-align: right; }
        .total td { font-weight: bold; border-bottom: none; }
        .note { margin-top: 14px; }
    </style>
</head>
<body>
    <h1>Purchase Order {{ $order->reference_number }}</h1>
    <div class="muted">Student Grade Portal &middot; Status: {{ ucfirst($order->status) }}</div>

    <div class="info">
        <strong>Supplier (Instructor):</strong> {{ $order->supplier->company_name ?? '—' }}<br>
        <strong>Order Date:</strong> {{ $order->order_date?->format('M d, Y') }}<br>
        <strong>Expected Delivery:</strong> {{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}<br>
        <strong>Created By:</strong> {{ $order->createdBy->name ?? '—' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th class="right">Points to Recover</th>
                <th class="right">Fee per Point</th>
                <th class="right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product->subject_code ?? '' }} - {{ $item->product->subject_name ?? '' }}</td>
                    <td class="right">{{ number_format($item->quantity_ordered, 2) }}</td>
                    <td class="right">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">₱{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="3" class="right">Subtotal</td>
                <td class="right">₱{{ number_format($order->subtotal, 2) }}</td>
            </tr>
            <tr class="total">
                <td colspan="3" class="right">Tax</td>
                <td class="right">₱{{ number_format($order->tax, 2) }}</td>
            </tr>
            <tr class="total">
                <td colspan="3" class="right">Total</td>
                <td class="right">₱{{ number_format($order->total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($order->notes)
        <div class="note"><strong>Notes:</strong> {{ $order->notes }}</div>
    @endif
    @if ($order->terms)
        <div class="note"><strong>Terms:</strong> {{ $order->terms }}</div>
    @endif
</body>
</html>