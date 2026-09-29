<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar colonia - SIGETA</title>
</head>
<body>

    <h1>SIGETA</h1>
    <h2>Editar colonia</h2>

    <form action="{{ route('updateZona', $zona->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div>
            <label for="nombre">Nombre de la colonia:</label>
            <input type="text" id="nombre" name="nombre" value="{{ $zona->nombre }}" required>
        </div>

        <br>

        <div>
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion">{{ $zona->descripcion }}</textarea>
        </div>

        <br>

        <div>
            <select name="activo" required>
                <option value="1" {{ $zona->activo ? 'selected' : '' }}>Activo</option>
                <option value="0" {{ !$zona->activo ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

        <br>

        <button type="submit">Guardar cambios</button>
    </form>

    <br>

    <button>
        <a href="{{ route('zonas.index') }}">Volver</a>
    </button>

</body>
</html>