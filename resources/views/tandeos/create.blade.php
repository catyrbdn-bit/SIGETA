<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programar tandeo - SIGETA</title>
</head>
<body>

    <h1>SIGETA</h1>
    <h2>Programar nuevo tandeo</h2>

    @if ($errors->any())
        <ul style="color:red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('storeTandeo') }}" method="POST">
        @csrf

        <div>
            <label for="circuito_id">Circuito:</label>
            <select id="circuito_id" name="circuito_id" required>
                @foreach ($circuitos as $circuito)
                    <option value="{{ $circuito->id }}">{{ $circuito->nombre }} ({{ $circuito->zona->nombre }})</option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" required>
        </div>

        <br>

        <div>
            <label for="hora_inicio_programada">Hora de inicio:</label>
            <input type="time" id="hora_inicio_programada" name="hora_inicio_programada" required>
        </div>

        <br>

        <div>
            <label for="tandeador_id">Tandeador asignado:</label>
            <select id="tandeador_id" name="tandeador_id">
                <option value="">Sin asignar</option>
                @foreach ($tandeadores as $tandeador)
                    <option value="{{ $tandeador->id }}">{{ $tandeador->nombre }}</option>
                @endforeach
            </select>
        </div>

        <br>

        <button type="submit">Guardar tandeo</button>
    </form>

    <br>

    <button>
        <a href="{{ route('tandeos.index') }}">Volver</a>
    </button>

</body>
</html>