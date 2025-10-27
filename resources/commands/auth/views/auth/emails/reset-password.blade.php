<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family:Arial, Helvetica, sans-serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:20px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                <tr>
                    <td style="padding:40px 30px 20px 30px; text-align:center;">
                        <h1 style="color:#333333; margin:0; font-size:24px;">Reset Your Password</h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 30px 30px 30px; text-align:left; color:#555555; font-size:16px; line-height:1.6;">
                        <p style="margin:0 0 20px 0;">Hi <strong>{{ $name }}</strong>,</p>
                        <p style="margin:0 0 20px 0;">
                            We received a request to reset your password. Click the button below to choose a new one.
                        </p>
                        <p style="margin:0 0 30px 0;">
                            If you didn’t request this, you can safely ignore this email.
                        </p>
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto;">
                            <tr>
                                <td align="center" bgcolor="#2563eb" style="border-radius:6px;">
                                    <a href="{{ $resetUrl }}"
                                       style="display:inline-block; padding:12px 25px; font-size:16px; color:#ffffff; text-decoration:none; font-weight:bold; background-color:#2563eb; border-radius:6px;">
                                        Change Password
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 30px; text-align:center; font-size:12px; color:#999999; background-color:#fafafa;">
                        <p style="margin:0;">If the button doesn’t work, copy and paste this link into your browser:</p>
                        <p style="margin:8px 0;"><a href="{{ $resetUrl }}" style="color:#2563eb; text-decoration:none;">{{ $resetUrl }}</a></p>
                        <p style="margin-top:15px;">&copy; {{ date('Y') }} Your Company. All rights reserved.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
