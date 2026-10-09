<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your email</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: Arial, Helvetica, sans-serif; color: #0f172a;">
    <div style="padding: 40px 20px;">
        <div style="max-width: 520px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px;">
            <div style="text-align: center; margin-bottom: 32px;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background-color: #0f172a; color: #ffffff; border-radius: 12px; font-size: 20px; font-weight: 700;">
                    H
                </div>
            </div>

        <h1 style="margin: 0 0 12px; font-size: 24px; line-height: 32px; text-align: center;">
            Verify your email
        </h1>

        <p style="margin: 0 0 24px; color: #64748b; font-size: 15px; line-height: 24px; text-align: center;">
            Hello {{ $user->first_name }}, use the verification code below to verify your HelpDesk account.
        </p>

        <div style="margin: 32px 0; padding: 20px; background-color: #f1f5f9; border-radius: 12px; text-align: center;">
            <span style="font-size: 32px; line-height: 40px; font-weight: 700; letter-spacing: 8px; color: #0f172a;">
                {{ $code }}
            </span>
        </div>

        <p style="margin: 0 0 8px; color: #64748b; font-size: 14px; line-height: 22px; text-align: center;">
            This code will expire in 10 minutes.
        </p>

        <p style="margin: 24px 0 0; color: #94a3b8; font-size: 13px; line-height: 20px; text-align: center;">
            If you did not create this account, you can safely ignore this email.
        </p>
    </div>
</div>