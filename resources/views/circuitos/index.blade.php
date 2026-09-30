<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Circuitos - SIGETA</title>

    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    
    <h1>Circuitos registrados</h1>

    <h3>Lista de Circuitos</h3>

    @if (session('success'))
    <p id="mensaje-exito" style="color:green;">{{ session('success') }}</p>
    <script>
        setTimeout(function() {
            document.getElementById('mensaje-exito').style.display = 'none';
        }, 3000);
    </script>
    @endif

    <button>
        <a href="{{ route('createCircuito') }}">Registrar nuevo circuito</a>
    </button>
    <br><br>

    <table>
        <thead>
            <tr>
                <th>Circuito</th>
                <th>Zona</th>
                <th>Duración (h)</th>
                <th>Activo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($circuitos as $circuito)
                <tr>
                    <td>{{ $circuito->nombre }}</td>
                    <td>{{ $circuito->zona->nombre }}</td>
                    <td>{{ $circuito->duracion }}</td>
                    <td>{{ $circuito->activo ? 'Sí' : 'No' }}</td>
                    <td>
                        <button>
                            <a href="{{ route('editCircuito', $circuito->id) }}">Editar</a>
                        </button>

                        <form action="{{ route('deleteCircuito', $circuito->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Desactivar este circuito?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Desactivar</button>
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