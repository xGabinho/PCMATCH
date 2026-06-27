<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña — PCMATCH</title>
</head>
<body style="margin: 0; padding: 0; background-color: #1A1A1A; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #1A1A1A; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 480px; background-color: #242424; border-radius: 16px; border: 1px solid #2E2E2E; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 32px 24px; text-align: center; border-bottom: 1px solid #2E2E2E;">
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="background-color: #3B82F6; border-radius: 10px; padding: 8px 12px; color: #FFFFFF; font-weight: bold; font-size: 14px;">PC</td>
                                    <td style="padding-left: 8px; color: #E0E0E0; font-size: 20px; font-weight: bold; letter-spacing: -0.5px;">PCMATCH</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px;">
                            <h1 style="margin: 0 0 8px; color: #E0E0E0; font-size: 20px; font-weight: 700;">Restablecer contraseña</h1>
                            <p style="margin: 0 0 24px; color: #9CA3AF; font-size: 14px; line-height: 1.6;">
                                Hola <strong style="color: #E0E0E0;">{{ $userName }}</strong>, recibimos una solicitud para restablecer la contraseña de tu cuenta.
                            </p>

                            <!-- Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0 24px;">
                                        <a href="{{ $resetUrl }}" target="_blank" style="display: inline-block; background-color: #3B82F6; color: #FFFFFF; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-size: 14px; font-weight: 600; letter-spacing: 0.3px;">
                                            Restablecer contraseña →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 16px; color: #9CA3AF; font-size: 13px; line-height: 1.6;">
                                Este enlace expirará en <strong style="color: #E0E0E0;">60 minutos</strong>. Si no solicitaste este cambio, puedes ignorar este correo.
                            </p>

                            <!-- Divider -->
                            <hr style="border: none; border-top: 1px solid #2E2E2E; margin: 24px 0;">

                            <p style="margin: 0; color: #6B7280; font-size: 12px; line-height: 1.5;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:
                            </p>
                            <p style="margin: 8px 0 0; word-break: break-all;">
                                <a href="{{ $resetUrl }}" style="color: #3B82F6; font-size: 12px; text-decoration: none;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 32px; background-color: #1E1E1E; border-top: 1px solid #2E2E2E; text-align: center;">
                            <p style="margin: 0; color: #6B7280; font-size: 11px;">
                                © {{ date('Y') }} PCMATCH — Gestión de componentes tecnológicos
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
