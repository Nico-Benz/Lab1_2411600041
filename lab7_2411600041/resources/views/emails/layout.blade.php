<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Student Supplies Inventory')</title>
</head>
<body style="margin:0; padding:0; background-color:#F5F3F8; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#231C35;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F5F3F8; padding:24px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#231C35; padding:20px 28px;">
                            <span style="color:#ffffff; font-size:20px; font-weight:bold;">Student Supplies Inventory</span>
                            <span style="color:#E67E22; font-size:13px; display:block; margin-top:2px;">@yield('tag', 'Notification')</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#F5F3F8; padding:14px 28px; font-size:12px; color:#6E5774;">
                            This is an automatic message from the Student Supplies Inventory system.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
