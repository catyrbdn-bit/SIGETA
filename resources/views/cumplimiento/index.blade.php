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

    <h1>Cumplimiento de tandeos de hoy</h1>

    @if (session('success'))
        <p id="mensaje-exito" style="color:green;">{{ session('success') }}</p>
        <script>
            setTimeout(function() {
                document.getElementById('mensaje-exito').style.display = 'none';
            }, 3000);
        </script>
    @endif

    <h3>Registrar apagón general</h3>
    <form action="{{ route('apagonGeneral') }}" method="POST">
        @csrf
        <label>Horas de atraso:</label>
        <select name="horas_atraso" required>
            <option value="1">1 hora</option>
            <option value="2">2 horas</option>
            <option value="3">3 horas</option>
            <option value="4">4 horas</option>
        </select>
        <button type="submit">Registrar apagón general</button>
    </form>

    <br>

    <h3>Registrar fuga</h3>
    <form action="{{ route('registrarFuga') }}" method="POST">
        @csrf
        <label>Zona:</label>
        <select name="zona_id" required>
            @foreach ($zonas as $zona)
                <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
            @endforeach
        </select>

        <label>Horas de atraso:</label>
        <select name="horas_atraso" required>
            <option value="1">1 hora</option>
            <option value="2">2 horas</option>
            <option value="3">3 horas</option>
        </select>

        <button type="submit">Registrar fuga</button>
    </form>

    <br>

    <h3>Tandeos de hoy</h3>
    <table>
        <thead>
            <tr>
                <th>Circuito</th>
                <th>Zona</th>
                <th>Hora programada</th>
                <th>Registrar cumplimiento</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tandeos as $tandeo)
                <tr>
                    <td>{{ $tandeo->circuito->nombre }}</td>
                    <td>{{ $tandeo->circuito->zona->nombre }}</td>
                    <td>{{ $tandeo->hora_inicio_programada }}</td>
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