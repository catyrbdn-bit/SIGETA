<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar colonia - SIGETA</title>
</head>
<body>
    
    <h1>SIGETA</h1>
    <h2>Registrar nueva colonia</h2>

    <form action="{{ route('storeZona') }}" method="POST">
        @csrf

        <div>
            <label for="nombre">Nombre de la colonia:</label>
            <input type="text" id="nombre" name="nombre" required>
        </div>

        <br>

        <div>
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion"></textarea>
        </div>

        <br>

        <div>
            <select name="activo" required>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>

        <br>

        <button type="submit">Guardar colonia</button>
    </form>

    <br>

    <button>
        <a href="{{ route('zonas.index') }}">Volver</a>
    </button>
</body>
</html>