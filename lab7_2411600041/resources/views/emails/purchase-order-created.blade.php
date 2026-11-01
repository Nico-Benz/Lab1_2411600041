<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order Created</title>
</head>
<body style="margin:0; padding:0; background-color:#F5F3F8; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; color:#231C35;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F5F3F8; padding:24px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#231C35; padding:24px 32px;">
                            <h1 style="margin:0; font-size:20px; color:#ffffff;">Student Grade Portal</h1>
                            <p style="margin:4px 0 0 0; font-size:13px; color:#E67E22;">Purchase Order Created</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px 0; font-size:15px;">
                                A new purchase order <strong>{{ $order->reference_number }}</strong> has been created.
                            </p>

                            <table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px; margin-bottom:20px;">
                                <tr>
                                    <td style="color:#6E5774; width:40%;">Supplier</td>
                                    <td><strong>{{ $order->supplier->company_name }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#6E5774;">Status</td>
                                    <td>{{ ucfirst($order->status) }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6E5774;">Order Date</td>
                                    <td>{{ $order->order_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6E5774;">Expected Delivery</td>
                                    <td>{{ $order->expected_delivery_date?->format('M d, Y') ?? 'Not set' }}</td>
                                </tr>
                            </table>

                            <!-- Line items -->
                            <table width="100%" cellpadding="8" cellspacing="0" style="font-size:13px; border-collapse:collapse;">
                                <thead>
                                    <tr style="background-color:#231C35; color:#ffffff;">
                                        <th align="left">Subject</th>
                                        <th align="right">Qty</th>
                                        <th align="right">Unit Price</th>
                                        <th align="right">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->items as $line)
                                        <tr style="border-bottom:1px solid #F5F3F8;">
                                            <td>{{ $line->product->subject_code }} - {{ $line->product->subject_name }}</td>
                                            <td align="right">{{ number_format((float) $line->quantity_ordered, 2) }}</td>
                                            <td align="right">{{ number_format((float) $line->unit_price, 2) }}</td>
                                            <td align="right">{{ number_format($line->line_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Totals -->
                            <table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px; margin-top:12px;">
                                <tr>
                                    <td align="right" style="color:#6E5774;">Subtotal</td>
                                    <td align="right" width="120">{{ number_format((float) $order->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td align="right" style="color:#6E5774;">Tax</td>
                                    <td align="right">{{ number_format((float) $order->tax, 2) }}</td>
                                </tr>
                                <tr>
                                    <td align="right"><strong>Total</strong></td>
                                    <td align="right" style="color:#E67E22;"><strong>{{ number_format((float) $order->total, 2) }}</strong></td>
                                </tr>
                            </table>

                            @if ($order->notes)
                                <p style="margin:20px 0 0 0; font-size:13px; color:#6E5774;">
                                    <strong>Notes:</strong> {{ $order->notes }}
                                </p>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#F5F3F8; padding:16px 32px; font-size:12px; color:#6E5774;">
                            This is an automated message from the Student Grade Portal.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>