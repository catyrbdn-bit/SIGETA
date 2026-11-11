<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso de tandeos</title>
</head>
<body style="margin: 0; padding: 24px 12px; background: #f1f5f9; color: #263445; font-family: Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #dbe3ec; border-radius: 10px;">
        <tr>
            <td style="padding: 22px 24px; background: #24577f; color: #fff; border-radius: 10px 10px 0 0;">
                <div style="font-size: 13px; letter-spacing: 1px;">SIGETA · AVISO DE SERVICIO</div>
                <h1 style="margin: 8px 0 0; font-size: 22px;">Cambios en los tandeos</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 22px 24px;">
                <p style="margin: 0 0 18px;">
                    Se registraron cambios por <strong>{{ $evento }}</strong>. Consulta los detalles:
                </p>

                @foreach ($cambios as $cambio)
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 0 0 16px; border: 1px solid #dbe3ec; border-radius: 8px;">
                        <tr>
                            <td style="padding: 15px 16px;">
                                <h2 style="margin: 0 0 12px; color: #24577f; font-size: 17px;">
                                    {{ $cambio['colonia'] }} · {{ $cambio['circuito'] }}
                                </h2>
                                <p>
                                    <strong>Lugar / tandeo:</strong><br>
                                    {{ $cambio['lugar'] }}
                                </p>
                                <p>
                                    <strong>Horario anterior:</strong><br>
                                    {{ $cambio['horario_anterior'] }}
                                </p>
                                <p style="color: #176b45;">
                                    <strong>Horario nuevo:</strong><br>
                                    {{ $cambio['horario_nuevo'] }}
                                </p>
                                <p>
                                    <strong>Motivo:</strong> {{ $cambio['motivo'] }}
                                </p>
                            </td>
                        </tr>
                    </table>
                @endforeach

                <p style="margin: 20px 0 0; padding-top: 14px; border-top: 1px solid #e5eaf0; color: #5e6b78; font-size: 13px;">
                    Aviso generado por el sistema de tandeos de SAPUA.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
