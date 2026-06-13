<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f5;
            color: #18181b;
        }
        .wrapper {
            max-width: 560px;
            margin: 40px auto;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #18181b;
        }
        h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 12px;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            color: #52525b;
            margin: 0 0 24px;
        }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            background-color: #18181b;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
        }
        .btn-wrap {
            text-align: center;
            margin: 0 0 24px;
        }
        .meta {
            font-size: 13px;
            color: #a1a1aa;
            line-height: 1.5;
        }
        .hr {
            border: none;
            border-top: 1px solid #e4e4e7;
            margin: 24px 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="logo">{{ config('app.name') }}</div>
            <h1>Reset Your Password</h1>
            <p>
                You are receiving this email because we received a password reset
                request for your account.
            </p>
            <div class="btn-wrap">
                <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
            </div>
            <p class="meta">
                This password reset link will expire in 60 minutes.<br>
                If you did not request a password reset, no further action is required.
            </p>
            <hr class="hr">
            <p class="meta">
                <strong>Account email</strong>: {{ $recipientEmail }}
            </p>
        </div>
    </div>
</body>
</html>
