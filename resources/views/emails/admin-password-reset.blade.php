<!DOCTYPE html>
<html lang="es" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Recuperación de Contraseña - {{ config('app.name') }}</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f7fb;
            color: #2d3748;
            line-height: 1.6;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f0f7fb;
            padding: 40px 0;
        }

        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .email-header {
            background-color: #ffffff;
            padding: 30px 40px;
            text-align: center;
            border-bottom: 3px solid #3b82f6;
        }

        .email-header img {
            max-height: 80px;
            margin-bottom: 15px;
        }

        .email-header h1 {
            color: #1e3a8a;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .email-body {
            padding: 40px;
            background-color: #ffffff;
        }

        .email-body p {
            font-size: 16px;
            color: #4b5563;
            margin: 0 0 20px 0;
        }

        .button-container {
            text-align: center;
            margin: 35px 0;
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            padding: 16px 36px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(2, 132, 199, 0.25);
            transition: all 0.3s ease;
        }

        .info-box {
            background-color: #f8fafc;
            border-left: 4px solid #38bdf8;
            padding: 16px 20px;
            border-radius: 0 8px 8px 0;
            margin: 24px 0;
        }

        .info-box p {
            font-size: 14px;
            color: #64748b;
            margin: 0;
        }

        .email-footer {
            background-color: #f1f5f9;
            padding: 24px 40px;
            text-align: center;
        }

        .email-footer p {
            font-size: 13px;
            color: #94a3b8;
            margin: 0 0 8px 0;
        }

        .raw-link {
            font-size: 12px;
            color: #64748b;
            word-break: break-all;
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }

        .raw-link a {
            color: #38bdf8;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td align="center">
                    <div class="email-content">
                        <!-- Header -->
                        <div class="email-header">
                            <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="{{ config('app.name') }} Logo">
                            <h1>Recuperación de Contraseña</h1>
                        </div>

                        <!-- Body -->
                        <div class="email-body">
                            <p><strong>¡Hola!</strong></p>
                            
                            <p>Recibes este correo porque hemos recibido una solicitud de restablecimiento de contraseña para tu cuenta de administrador en <strong>{{ config('app.name') }}</strong>.</p>

                            <div class="button-container">
                                <a href="{{ $url }}" class="button" target="_blank">Restablecer Contraseña</a>
                            </div>

                            <div class="info-box">
                                <p>⏱️ Este enlace de restablecimiento de contraseña expirará en 15 minutos.</p>
                            </div>

                            <p>Si no solicitaste un restablecimiento de contraseña, puedes ignorar o eliminar este mensaje de forma segura.</p>

                            <p style="margin-top: 30px;">
                                Saludos cordiales,<br>
                                <strong>El equipo de {{ config('app.name') }}</strong>
                            </p>

                            <!-- Fallback Link -->
                            <div class="raw-link">
                                <p>Si tienes problemas haciendo clic en el botón "Restablecer Contraseña", copia y pega la siguiente URL en tu navegador web:</p>
                                <a href="{{ $url }}">{{ $url }}</a>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="email-footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
