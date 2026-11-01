<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order Approved</title>
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
                            <p style="margin:4px 0 0 0; font-size:13px; color:#E67E22;">Purchase Order Approved</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px 0; font-size:15px;">
                                Good news! Purchase order <strong>{{ $order->reference_number }}</strong> has been approved.
                            </p>

                            <table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px; margin-bottom:20px;">
                                <tr>
                                    <td style="color:#6E5774; width:40%;">Supplier</td>
                                    <td><strong>{{ $order->supplier->company_name }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#6E5774;">Approved By</td>
                                    <td>{{ $order->approvedBy?->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6E5774;">Approved On</td>
                                    <td>{{ $order->approved_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6E5774;">Expected Delivery</td>
                                    <td>{{ $order->expected_delivery_date?->format('M d, Y') ?? 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6E5774;">Total</td>
                                    <td style="color:#E67E22;"><strong>{{ number_format((float) $order->total, 2) }}</strong></td>
                                </tr>
                            </table>

                            <p style="margin:0; font-size:14px;">
                                The next step is to send this order to the supplier.
                            </p>
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