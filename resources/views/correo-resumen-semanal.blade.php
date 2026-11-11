<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen semanal de tandeos</title>
</head>
<body style="margin: 0; padding: 24px 12px; background: #f1f5f9; color: #263445; font-family: Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 720px; margin: 0 auto; background: #fff; border: 1px solid #dbe3ec; border-radius: 10px;">
        <tr>
            <td style="padding: 22px 24px; background: #24577f; color: #fff; border-radius: 10px 10px 0 0;">
                <div style="font-size: 13px; letter-spacing: 1px;">SIGETA · PROGRAMACIÓN</div>
                <h1 style="margin: 8px 0 0; font-size: 22px;">Resumen semanal de tandeos</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 22px 24px;">
                <p>
                    Programación del <strong>{{ $inicio }}</strong> al <strong>{{ $fin }}</strong>.
                </p>

                @if ($filas->isEmpty())
                    <p style="padding: 14px; background: #f5f8fb; border-radius: 8px;">
                        No hay tandeos programados para este periodo.
                    </p>
                @else
                    <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background: #eaf1f7;">
                                <th align="left" style="padding: 10px;">Fecha</th>
                                <th align="left" style="padding: 10px;">Colonia</th>
                                <th align="left" style="padding: 10px;">Lugar / tandeo</th>
                                <th align="left" style="padding: 10px;">Horario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($filas as $fila)
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #e5eaf0; white-space: nowrap;">
                                        {{ $fila['fecha'] }}
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #e5eaf0;">
                                        {{ $fila['colonia'] }}
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #e5eaf0;">
                                        {{ $fila['lugar'] }}
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #e5eaf0; white-space: nowrap;">
                                        {{ $fila['horario'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <p style="margin: 20px 0 0; padding-top: 14px; border-top: 1px solid #e5eaf0; color: #5e6b78; font-size: 13px;">
                    Resumen generado automáticamente por SIGETA.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
