<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Circuito con tandeos - SIGETA</title>
</head>
<body>

    <h1>No se puede eliminar este circuito</h1>

    <p>El circuito <strong>{{ $circuito->nombre }}</strong> tiene tandeos asociados y no puede eliminarse de forma definitiva.</p>

    <h3>Tandeos asociados</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tandeosAsociados as $tandeo)
                <tr>
                    <td>{{ $tandeo->fecha }}</td>
                    <td>{{ \Carbon\Carbon::parse($tandeo->hora_inicio_programada)->format('G:i') }}-{{ \Carbon\Carbon::parse($tandeo->hora_fin_programada)->format('G:i') }}</td>
                    <td>{{ $tandeo->estado }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>

    <form action="{{ route('desactivarCircuito', $circuito->id) }}" method="POST">
        @csrf
        @method('PUT')
        <button type="submit">Desactivar circuito en su lugar</button>
    </form>

    <br>

    <button>
        <a href="{{ route('circuitos.index') }}">Cancelar</a>
    </button>

</body>
</html>