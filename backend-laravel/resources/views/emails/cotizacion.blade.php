<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Cotización en PCMATCH</title>
</head>
<body style="margin: 0; padding: 0; background-color: #1A1A1A; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #1A1A1A; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 550px; background-color: #242424; border-radius: 16px; border: 1px solid #2E2E2E; overflow: hidden;">
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
                            <h1 style="margin: 0 0 16px; color: #E0E0E0; font-size: 22px; font-weight: 700; text-align: center;">Tu Cotización está Lista</h1>
                            <p style="margin: 0 0 20px; color: #9CA3AF; font-size: 15px; line-height: 1.6;">
                                Hola <strong style="color: #E0E0E0;">{{ $user->nombre }}</strong>,
                            </p>
                            <p style="margin: 0 0 24px; color: #9CA3AF; font-size: 15px; line-height: 1.6;">
                                Gracias por utilizar PCMATCH para planificar tu próxima computadora. Hemos adjuntado el documento PDF detallado con todos los componentes que seleccionaste.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #1E1E1E; border: 1px solid #2E2E2E; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px; text-align: center;">
                                        <span style="display: block; color: #6B7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Código de Cotización</span>
                                        <strong style="color: #3B82F6; font-size: 20px; letter-spacing: 1px;">{{ $cotizacion->codigo }}</strong>
                                    </td>
                                    <td style="padding: 16px; text-align: center; border-left: 1px solid #2E2E2E;">
                                        <span style="display: block; color: #6B7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Total Estimado</span>
                                        <strong style="color: #10B981; font-size: 20px;">${{ number_format($cotizacion->total, 2) }}</strong>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; color: #9CA3AF; font-size: 14px; line-height: 1.6; text-align: center;">
                                Revisa el archivo adjunto para ver el detalle de cada componente. Si tienes alguna duda o deseas proceder con la compra, puedes contactarnos respondiendo a este correo.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 32px; background-color: #1E1E1E; border-top: 1px solid #2E2E2E; text-align: center;">
                            <p style="margin: 0; color: #6B7280; font-size: 12px;">
                                © {{ date('Y') }} PCMATCH — La plataforma para construir tu PC ideal.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
