<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Sleepywear Inventory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Base Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f9;
            color: #333333;
            -webkit-font-smoothing: antialiased;
        }

        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f4f7f9;
            padding-bottom: 40px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-top: 40px;
        }

        /* Header Area */
        .header {
            background-color: #1a2260;
            padding: 30px;
            text-align: center;
            color: #ffffff;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sub-header {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 5px;
        }

        /* Content Area */
        .content {
            padding: 40px 30px;
            text-align: center;
        }

        .icon-circle {
            width: 70px;
            height: 70px;
            background-color: rgba(26, 34, 96, 0.05);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 20px;
            color: #1a2260;
            line-height: 70px; /* Center icon vertically */
        }

        h2 {
            color: #1a2260;
            font-size: 22px;
            margin-bottom: 15px;
        }

        p {
            color: #555555;
            line-height: 1.6;
            margin-bottom: 30px;
            font-size: 16px;
        }

        /* Button Action */
        .btn {
            display: inline-block;
            background-color: #1a2260;
            color: #ffffff !important;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        /* Secondary Text Section */
        .secondary-text {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid #eeeeee;
        }

        .secondary-text p {
            font-size: 13px;
            color: #8898aa;
            margin-bottom: 10px;
        }

        .url-link {
            word-break: break-all;
            color: #1a2260;
            text-decoration: none;
            font-size: 13px;
        }

        /* Footer Area */
        .footer {
            text-align: center;
            padding: 30px;
        }

        .footer p {
            font-size: 12px;
            color: #8898aa;
            margin: 0;
        }

        /* Mobile Optimization */
        @media only screen and (max-width: 600px) {
            .container {
                margin-top: 0;
                border-radius: 0;
            }
            .content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>Sleepywear</h1>
                <div class="sub-header">Inventory Management System</div>
            </div>

            <div class="content">
                <h2>Password Reset Request</h2>
                <p>Hello,</p>
                <p>We received a request to reset your Sleepywear Inventory account password. Click the button below to choose a new password.</p>

                <a href="{{ $resetUrl }}" class="btn">Reset Password</a>

                <div class="secondary-text">
                    <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                    <p>Button not working? Copy and paste the link below into your browser:</p>
                    <a href="{{ $resetUrl }}" class="url-link">{{ $resetUrl }}</a>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ $year }} Sleepywear Inventory. All rights reserved.</p>
            <p>Apalit, Pampanga</p>
        </div>
    </div>
</body>
</html>
