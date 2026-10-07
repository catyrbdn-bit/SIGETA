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
                    <option value="{{ $zona->id }}" @selected(old('zona_id') == $zona->id)>{{ $zona->nombre }}</option>
                @endforeach
            </select>
            <br>
            <small>El código del circuito (por ejemplo C01-LJ) se genera automáticamente al guardar.</small>
        </div>

        <br>

        <div>
            <label for="descripcion">Descripción (calles):</label>
            <input type="text" id="descripcion" name="descripcion" maxlength="255" size="50"
                   value="{{ old('descripcion') }}" placeholder="Ej. Lauro Ortega y Pipila por las antenas">
        </div>

        <br>

        <div>
            <label for="duracion">Duración (horas):</label>
            <input type="number" id="duracion" name="duracion" step="0.5" value="{{ old('duracion') }}" required>
        </div>

        <br>

        <div>
            <label for="punto_abastecimiento_id">Punto de abastecimiento:</label>
            <select id="punto_abastecimiento_id" name="punto_abastecimiento_id">
                <option value="">Sin punto fijo</option>
                @foreach ($puntos as $punto)
                    <option value="{{ $punto->id }}" @selected(old('punto_abastecimiento_id') == $punto->id)>
                        {{ $punto->nombre }}
                    </option>
                @endforeach
            </select>
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