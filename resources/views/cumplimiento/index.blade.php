<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cumplimiento de tandeos - SIGETA</title>

    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
    </style>
</head>
<body>

    <h1>Cumplimiento de tandeos de la semana</h1>

    @if (session('success'))
        <p id="mensaje-exito" style="color:green;">{{ session('success') }}</p>
        <script>
            setTimeout(function() {
                document.getElementById('mensaje-exito').style.display = 'none';
            }, 3000);
        </script>
    @endif

    <div style="margin-bottom: 15px;">
        <form action="{{ route('reprogramaciones.apagon.previa') }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit">Apagón general</button>
        </form>

        {{-- Se activa cuando armemos el flujo de la fuga --}}
        <button type="button" disabled>
            Fuga
        </button>
    </div>

    <h3>Tandeos de la semana</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Circuito</th>
                <th>Zona</th>
                <th>Horario programado</th>
                <th>Registrar cumplimiento</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tandeos as $tandeo)
                <tr style="{{ $tandeo->fecha->isToday() ? 'background:#e8f4ff;' : '' }}">
                    <td>{{ $tandeo->fecha->format('d/m/Y') }}</td>
                    <td>{{ $tandeo->circuito->nombre }}</td>
                    <td>{{ $tandeo->circuito->zona->nombre }}</td>
                    <td>{{ substr($tandeo->hora_inicio_programada, 0, 5) }} - {{ substr($tandeo->hora_fin_programada, 0, 5) }}</td>
                    <td>
                        <form action="{{ route('storeCumplimiento') }}" method="POST">
                            @csrf
                            <input type="hidden" name="tandeo_id" value="{{ $tandeo->id }}">

                            <select name="resultado" required>
                                <option value="cumplido">Cumplido</option>
                                <option value="atraso">Con atraso</option>
                                <option value="no_cumplido">No cumplido</option>
                            </select>

                            <select name="horas_atraso">
                                <option value="">Sin atraso</option>
                                <option value="1">1 hora</option>
                                <option value="2">2 horas</option>
                                <option value="3">3 horas</option>
                                <option value="4">4 horas</option>
                            </select>

                            <input type="text" name="observaciones" placeholder="Observaciones">

                            <button type="submit">Guardar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>

    <button>
        <a href="{{ route('dashboard') }}">Volver</a>
    </button>

</body>
</html>