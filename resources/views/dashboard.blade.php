<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel principal - SIGETA</title>

    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
    </style>
</head>

<body>

    <h1>SIGETA</h1>

    <h2>Panel principal</h2>

    <p>
        Bienvenido, {{ Auth::user()->nombre }}
    </p>

    <p>
        Usuario: {{ Auth::user()->usuario }}
    </p>

    <p>
        Rol: {{ Auth::user()->rol }}
    </p>

    <h3>Tandeos de hoy</h3>

    <table>
        <thead>
            <tr>
                <th>Circuito</th>
                <th>Zona</th>
                <th>Hora programada</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tandeosHoy as $tandeo)
                <tr>
                    <td>{{ $tandeo->circuito->nombre }}</td>
                    <td>{{ $tandeo->circuito->zona->nombre }}</td>
                    <td>{{ $tandeo->hora_inicio_programada }}</td>
                    <td>{{ $tandeo->estado }}</td>
                    <td>
                        <button>
                            <a href="{{ route('editTandeo', $tandeo->id) }}">Editar</a>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>

    <button>
        <a href="{{ route('zonas.index') }}">Colonias</a>
    </button>

    <br><br>
    
    <button> 
        <a href="{{ route('circuitos.index') }}">Circuitos</a>
    </button>
    <br><br>

    <button>
        <a href="{{ route('tandeos.index') }}">Tandeos Programados</a>
    </button>
    <br><br>

    <button>
    <a href="{{ route('cumplimiento.index') }}">Cumplimiento de Tandeos</a>
    </button>
    <br><br>

    <form action="{{ route('cerrarSesion') }}" method="POST">

        @csrf

        <button type="submit">
            Cerrar sesión
        </button>

    </form>

</body>
</html>