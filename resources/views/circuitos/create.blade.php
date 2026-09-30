<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar circuito - SIGETA</title>
</head>
<body>

    <h1>SIGETA</h1>
    <h2>Registrar nuevo circuito</h2>

    @if ($errors->any())
        <ul style="color:red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('storeCircuito') }}" method="POST">
        @csrf

        <div>
            <label for="zona_id">Zona:</label>
            <select id="zona_id" name="zona_id" required>
                @foreach ($zonas as $zona)
                    <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label for="nombre">Nombre del circuito:</label>
            <input type="text" id="nombre" name="nombre" required>
        </div>

        <br>

        <div>
            <label for="duracion">Duración (horas):</label>
            <input type="number" id="duracion" name="duracion" step="0.5" required>
        </div>

        <br>

        <div>
            <select name="activo" required>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>

        <br>

        <button type="submit">Guardar circuito</button>
    </form>

    <br>

    <button>
        <a href="{{ route('circuitos.index') }}">Volver</a>
    </button>

</body>
</html>