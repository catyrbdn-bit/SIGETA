<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista previa - SIGETA</title>

    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
    </style>
</head>
<body>

    <h1>SIGETA</h1>
    <h2>Vista previa: apagón general</h2>
    <p>Fecha:  {{ $desde->format('d/m/Y H:i') }}</p>

    @foreach ($resultado['advertencias'] as $aviso)
        <p style="color:#b36b00;">⚠ {{ $aviso }}</p>
    @endforeach

    @if (count($resultado['movimientos']))
        <table>
            <thead>
                <tr>
                    <th>Zona</th>
                    <th>Circuito</th>
                    <th>Punto de abastecimiento</th>
                    <th>Horario actual</th>
                    <th>Horario nuevo</th>
                    <th>Atraso</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($resultado['movimientos'] as $m)
                    <tr>
                        <td>{{ $m['tandeo']->circuito->zona->nombre ?? 'Sin zona' }}</td>
                        <td>{{ $m['tandeo']->circuito->nombre }}</td>
                        <td>{{ $m['tandeo']->circuito->puntoAbastecimiento->nombre ?? 'Sin punto fijo' }}</td>
                        <td>{{ $m['inicio_anterior']->format('d/m H:i') }} - {{ $m['fin_anterior']->format('d/m H:i') }}</td>
                        <td>{{ $m['inicio_nuevo']->format('d/m H:i') }} - {{ $m['fin_nuevo']->format('d/m H:i') }}</td>
                        <td>{{ round($m['inicio_anterior']->diffInMinutes($m['inicio_nuevo']) / 60, 1) }} h</td>
                        <td>{{ $m['es_cadena'] ? 'Recorrido por empalme' : 'Afectado por el apagón' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <br>

        <form action="{{ route('reprogramaciones.apagon.store') }}" method="POST">
            @csrf
            <input type="hidden" name="desde" value="{{ $desde->format('Y-m-d\TH:i') }}">

            <button type="submit">Confirmar y guardar</button>
            <button type="button" onclick="window.location.href='{{ route('cumplimiento.index') }}'">Cancelar</button>
        </form>
    @else
        <p>No hay tandeos de hoy afectados por el apagon.</p>

        <button>
            <a href="{{ route('cumplimiento.index') }}">Volver</a>
        </button>
    @endif

</body>
</html>