<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar circuito - SIGETA</title>
</head>
<body>

    <h1>SIGETA</h1>
    <h2>Editar circuito</h2>

    @if ($errors->any())
        <ul style="color:red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('updateCircuito', $circuito->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div>
            <label for="zona_id">Zona:</label>
            <select id="zona_id" name="zona_id" required>
                @foreach ($zonas as $zona)
                    <option value="{{ $zona->id }}" {{ $circuito->zona_id == $zona->id ? 'selected' : '' }}>
                        {{ $zona->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label for="nombre">Nombre del circuito:</label>
            <input type="text" id="nombre" name="nombre" value="{{ $circuito->nombre }}" required>
        </div>

        <br>

        <div>
            <label for="duracion">Duración (horas):</label>
            <input type="number" id="duracion" name="duracion" value="{{ $circuito->duracion }}" required>
        </div>

        <br>

        <div>
            <label for="punto_abastecimiento_id">Punto de abastecimiento:</label>
            <select id="punto_abastecimiento_id" name="punto_abastecimiento_id">
                <option value="">Sin punto fijo</option>
                @foreach ($puntos as $punto)
                    <option value="{{ $punto->id }}"
                        {{ old('punto_abastecimiento_id', $circuito->punto_abastecimiento_id) == $punto->id ? 'selected' : '' }}>
                        {{ $punto->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <select name="activo" required>
                <option value="1" {{ $circuito->activo ? 'selected' : '' }}>Activo</option>
                <option value="0" {{ !$circuito->activo ? 'selected' : '' }}>Inactivo</option>
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