<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tandeos programados - SIGETA</title>

    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
    </style>
</head>
<body>

    <h1>Tandeos programados</h1>

    @if (session('success'))
        <p id="mensaje-exito" style="color:green;">{{ session('success') }}</p>
        <script>
            setTimeout(function() {
                document.getElementById('mensaje-exito').style.display = 'none';
            }, 3000);
        </script>
    @endif

    <button>
        <a href="{{ route('createTandeo') }}">Programar nuevo tandeo</a>
    </button>
    <br><br>

    <table>
        <thead>
            <tr>
                <th>Circuito</th>
                <th>Zona</th>
                <th>Fecha</th>
                <th>Hora inicio</th>
                <th>Hora fin</th>
                <th>Estado</th>
                <th>Tandeador</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tandeos as $tandeo)
                <tr>
                    <td>{{ $tandeo->circuito->nombre }}</td>
                    <td>{{ $tandeo->circuito->zona->nombre }}</td>
                    <td>{{ $tandeo->fecha }}</td>
                    <td>{{ $tandeo->hora_inicio_programada }}</td>
                    <td>{{ $tandeo->hora_fin_programada }}</td>
                    <td>{{ $tandeo->estado }}</td>
                    <td>{{ $tandeo->tandeador->nombre ?? 'Sin asignar' }}</td>
                    <td>
                        <button>
                            <a href="{{ route('editTandeo', $tandeo->id) }}">Editar</a>
                        </button>

                        <form action="{{ route('deleteTandeo', $tandeo->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este tandeo?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Eliminar</button>
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