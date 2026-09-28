<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel principal - SIGETA</title>
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

    <form action="{{ route('cerrarSesion') }}" method="POST">

        @csrf

        <button type="submit">
            Cerrar sesión
        </button>

    </form>

</body>
</html>