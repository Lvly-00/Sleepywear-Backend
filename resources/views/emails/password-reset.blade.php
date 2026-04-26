<!DOCTYPE html>
<html>

<head>
    <title>Verification Code - Sleepywears Inventory</title>
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

        h2 {
            color: #1a2260;
            font-size: 22px;
            margin-bottom: 15px;
        }

        p {
            color: #555555;
            line-height: 1.6;
            margin-bottom: 25px;
            font-size: 16px;
        }

        /* OTP Code Box */
        .otp-container {
            background-color: #f8f9fa;
            border: 2px dashed #1a2260;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            display: inline-block;
        }

        .otp-code {
            font-size: 38px;
            font-weight: 800;
            color: #1a2260;
            letter-spacing: 8px;
            margin: 0;
        }

        /* Secondary Text Section */
        .secondary-text {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #eeeeee;
        }

        .secondary-text p {
            font-size: 13px;
            color: #8898aa;
            margin-bottom: 10px;
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

            .otp-code {
                font-size: 32px;
                letter-spacing: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>Sleepywears</h1>
                <div class="sub-header">Inventory Management System</div>
            </div>

            <div class="content">
                <h2>
                    {{ $purpose === 'biometric' ? 'Biometric Registration Code' : 'Password Reset Code' }}
                </h2>

                <p>Hello,</p>

                <p>
                    @if ($purpose === 'biometric')
                        You requested to enable biometric login for your Sleepywears Inventory account.
                        Please use the verification code below to continue:
                    @else
                        We received a request to reset your Sleepywears Inventory account password.
                        Please enter the following code in the application to proceed:
                    @endif
                </p>

                <div class="otp-container">
                    <h1 class="otp-code">{{ $otp }}</h1>
                </div>

                @if ($purpose === 'biometric')
                    <p> This code will expire in 10 minutes for security reasons.</p>
                @else
                    <p> This code will expire in 60 minutes for security reasons.</p>
                @endif

                <div class="secondary-text">
                    @if ($purpose === 'biometric')
                        <p> If you did not request biometric setup, please ignore this email.</p>
                    @else
                        <p> If you did not request a password reset, please ignore this email. </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ $year }} Sleepywears Inventory. All rights reserved.</p>
            <p>Apalit, Pampanga</p>
        </div>
    </div>
</body>

</html>
