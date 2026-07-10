<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 24px; background: #ffffff; color: #111827;">
    <div style="max-width: 560px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
        <h2 style="margin: 0 0 16px;">TenantFlow Login OTP</h2>
        <p>Hello {{ $name }},</p>
        <p>Use the following OTP to log in to your TenantFlow account:</p>
        <div style="font-size: 36px; letter-spacing: 8px; text-align: center; padding: 16px; background: #f5f5f5; border-radius: 8px; font-weight: 700; margin: 20px 0;">
            {{ $otp }}
        </div>
        <p>This OTP is valid for 5 minutes.</p>
        <p>If you did not request this, please ignore this email.</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
        <p style="color: #6b7280; margin: 0;">TenantFlow - PG Management System</p>
    </div>
</body>
</html>