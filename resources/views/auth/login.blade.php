<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inicio de sesión - SIGETA</title>
</head>

<body>

    <h1>SIGETA DE SAPUA</h1>
    <h2>Inicio de sesión</h2>

    @if ($errors->any())
        <div>
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('iniciarSesion') }}" method="POST">

        @csrf

        <div>
            <label for="usuario">Usuario:</label>
            <input
                type="text"
                id="usuario"
                name="usuario"
                value="{{ old('usuario') }}"
                required
            >
        </div>

        <br>

        <div>
            <label for="password">Contraseña:</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <br>

        <button type="submit">
            Iniciar sesión
        </button>

    </form>

</body>
</html>