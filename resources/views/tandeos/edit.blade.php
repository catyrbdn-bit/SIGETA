<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar tandeo - SIGETA</title>
</head>
<body>

    <h1>SIGETA</h1>
    <h2>Editar tandeo</h2>

    @if ($errors->any())
        <ul style="color:red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('updateTandeo', $tandeo->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div>
            <label for="circuito_id">Circuito:</label>
            <select id="circuito_id" name="circuito_id" required>
                @foreach ($circuitos as $circuito)
                    <option value="{{ $circuito->id }}" {{ $tandeo->circuito_id == $circuito->id ? 'selected' : '' }}>
                        {{ $circuito->nombre }} ({{ $circuito->zona->nombre }})
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" value="{{ $tandeo->fecha }}" required>
        </div>

        <br>

        <div>
            <label for="hora_inicio_programada">Hora de inicio:</label>
            <input type="time" id="hora_inicio_programada" name="hora_inicio_programada" value="{{ $tandeo->hora_inicio_programada }}" required>
        </div>

        <br>

        <div>
            <label for="tandeador_id">Tandeador asignado:</label>
            <select id="tandeador_id" name="tandeador_id">
                <option value="">Sin asignar</option>
                @foreach ($tandeadores as $tandeador)
                    <option value="{{ $tandeador->id }}" {{ $tandeo->tandeador_id == $tandeador->id ? 'selected' : '' }}>
                        {{ $tandeador->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <button type="submit">Guardar cambios</button>
    </form>

    <br>

    <button>
        <a href="{{ url()->previous() }}">Volver</a>
    </button>

</body>
</html>