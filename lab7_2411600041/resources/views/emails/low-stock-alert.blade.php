<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Low Grade Alert</title>
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
                            <p style="margin:4px 0 0 0; font-size:13px; color:#E67E22;">At Risk Alert</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px 0; font-size:15px;">
                                The subject <strong>{{ $item->subject_code }} - {{ $item->subject_name }}</strong>
                                is now <strong style="color:#D9534F;">At Risk</strong>. Its grade is below the threshold.
                            </p>

                            <table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px; margin-bottom:20px;">
                                <tr>
                                    <td style="color:#6E5774; width:40%;">Current Grade</td>
                                    <td style="color:#D9534F;"><strong>{{ number_format((float) $item->current_grade, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#6E5774;">Threshold</td>
                                    <td>{{ number_format((float) $item->low_grade_threshold, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6E5774;">Points to Recover</td>
                                    <td>{{ number_format(max(0, (float) $item->low_grade_threshold - (float) $item->current_grade), 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6E5774;">Instructor</td>
                                    <td>{{ $item->supplier?->company_name ?? $item->instructor ?? 'Not assigned' }}</td>
                                </tr>
                            </table>

                            <p style="margin:0; font-size:14px;">
                                Please arrange a remedial request for this subject.
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