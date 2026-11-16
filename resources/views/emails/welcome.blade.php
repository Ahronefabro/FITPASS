<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="supported-color-schemes" content="dark">
    <title>Welcome to FitPass Gym</title>
</head>
<body style="margin:0; padding:0; background-color:#141414; font-family:'Inter','Segoe UI',Arial,sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#141414" style="background-color:#141414;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <!-- Container -->
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:560px;">

                    <!-- Logo -->
                    <tr>
                        <td align="center" style="padding:0 0 24px 0;">
                            <div style="font-size:30px; font-weight:800; letter-spacing:1px; line-height:1;">
                                <span style="color:#ffffff;">FIT</span><span style="color:#00ff66;">PASS</span>
                            </div>
                            <div style="font-size:11px; letter-spacing:6px; color:#9a9a9a; margin-top:6px;">G Y M</div>
                        </td>
                    </tr>

                    <!-- Main card -->
                    <tr>
                        <td bgcolor="#222222" style="background-color:#222222; border:1px solid #333333; border-radius:14px; border-top:3px solid #00ff66;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">

                                <!-- Heading -->
                                <tr>
                                    <td style="padding:36px 36px 8px 36px;">
                                        <div style="font-size:12px; font-weight:700; letter-spacing:2px; color:#00ff66; text-transform:uppercase;">
                                            Welcome aboard
                                        </div>
                                        <h1 style="margin:10px 0 0 0; font-size:28px; line-height:1.25; color:#ffffff; font-weight:800;">
                                            Hi {{ $member->name }}, you're in!
                                        </h1>
                                    </td>
                                </tr>

                                <!-- Body text -->
                                <tr>
                                    <td style="padding:12px 36px 24px 36px; font-size:15px; line-height:1.7; color:#c4c4c4;">
                                        Thanks for signing up at <strong style="color:#ffffff;">FitPass Gym</strong>.
                                        Your account is ready, so you can start checking in and tracking your workouts right away.
                                    </td>
                                </tr>

                                <!-- Info cards -->
                                <tr>
                                    <td style="padding:0 36px 28px 36px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="50%" valign="top" style="padding-right:6px;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#2b2b2b" style="background-color:#2b2b2b; border:1px solid #363636; border-radius:10px;">
                                                        <tr>
                                                            <td style="padding:16px;">
                                                                <div style="font-size:12px; color:#9a9a9a;">Step 1</div>
                                                                <div style="font-size:15px; font-weight:700; color:#ffffff; margin-top:4px;">Open the app</div>
                                                                <div style="font-size:12px; color:#00ff66; margin-top:4px;">Log in to your account</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="50%" valign="top" style="padding-left:6px;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#2b2b2b" style="background-color:#2b2b2b; border:1px solid #363636; border-radius:10px;">
                                                        <tr>
                                                            <td style="padding:16px;">
                                                                <div style="font-size:12px; color:#9a9a9a;">Step 2</div>
                                                                <div style="font-size:15px; font-weight:700; color:#ffffff; margin-top:4px;">Scan your QR</div>
                                                                <div style="font-size:12px; color:#00ff66; margin-top:4px;">Check in at the gym</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Button -->
                                <tr>
                                    <td align="center" style="padding:0 36px 36px 36px;">
                                        <a href="{{ config('app.url') }}"
                                           style="display:inline-block; background-color:#00ff66; color:#0f0f0f; font-size:15px; font-weight:800; text-decoration:none; padding:14px 36px; border-radius:10px; letter-spacing:0.5px;">
                                            Go to FitPass
                                        </a>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding:24px 16px 0 16px; font-size:12px; line-height:1.6; color:#7a7a7a;">
                            If you didn't create this account, you can safely ignore this email.<br>
                            &copy; {{ date('Y') }} FitPass Gym. All rights reserved.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>