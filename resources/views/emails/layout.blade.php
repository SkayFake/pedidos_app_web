<!DOCTYPE html>
<html lang="es" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ config('app.name') }}</title>
    <style>
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }

        /* Base Styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f6f9;
            color: #2d3748;
            line-height: 1.6;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f4f6f9;
            padding: 40px 0;
        }

        .email-content {
            max-width: 580px;
            margin: 0 auto;
        }

        /* Header */
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px 12px 0 0;
            padding: 32px 40px;
            text-align: center;
        }

        .email-header h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .email-header .app-name {
            color: rgba(255, 255, 255, 0.85);
            font-size: 13px;
            font-weight: 500;
            margin: 0 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        /* Body */
        .email-body {
            background-color: #ffffff;
            padding: 40px;
        }

        .email-body p {
            font-size: 15px;
            line-height: 1.7;
            color: #4a5568;
            margin: 0 0 16px 0;
        }

        .email-body .greeting {
            font-size: 17px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
        }

        /* OTP Code Box */
        .otp-container {
            text-align: center;
            margin: 28px 0;
        }

        .otp-code {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 8px;
            padding: 18px 36px;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
        }

        .otp-expiry {
            display: inline-block;
            background-color: #fef3cd;
            color: #856404;
            font-size: 13px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            margin-top: 12px;
        }

        /* Info Box */
        .info-box {
            background-color: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 16px 20px;
            border-radius: 0 8px 8px 0;
            margin: 24px 0;
        }

        .info-box p {
            font-size: 13px;
            color: #718096;
            margin: 0;
        }

        /* Footer */
        .email-footer {
            background-color: #f8f9fb;
            border-top: 1px solid #e8ecf1;
            border-radius: 0 0 12px 12px;
            padding: 24px 40px;
            text-align: center;
        }

        .email-footer p {
            font-size: 12px;
            color: #a0aec0;
            margin: 0 0 4px 0;
            line-height: 1.5;
        }

        .email-footer .company-name {
            font-weight: 600;
            color: #718096;
        }

        /* Responsive */
        @media only screen and (max-width: 620px) {
            .email-content { width: 100% !important; }
            .email-header, .email-body, .email-footer { padding-left: 24px !important; padding-right: 24px !important; }
            .otp-code { font-size: 26px; letter-spacing: 6px; padding: 14px 28px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td align="center">
                    <div class="email-content">
                        {{-- Header --}}
                        <div class="email-header">
                            <p class="app-name">{{ config('app.name') }}</p>
                            <h1>@yield('title')</h1>
                        </div>

                        {{-- Body --}}
                        <div class="email-body">
                            @yield('content')
                        </div>

                        {{-- Footer --}}
                        <div class="email-footer">
                            <p class="company-name">{{ config('app.name') }}</p>
                            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
