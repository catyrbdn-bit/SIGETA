<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión - SIGETA</title>

    <style>
        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            color: #243447;
            background: #f5f7fa;
        }

        .contenedor { width: 100%; max-width: 420px; }
        h1 { margin-bottom: 6px; color: #1f4e79; }
        h2 { margin-top: 0; margin-bottom: 22px; }

        .panel {
            padding: 24px;
            border: 1px solid #dce3ea;
            border-radius: 10px;
            background: #fff;
        }

        .campo { margin-bottom: 18px; }
        label { display: block; margin-bottom: 7px; font-weight: bold; }
        input {
            width: 100%;
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid #b9c4ce;
            border-radius: 6px;
            font-size: 15px;
        }

        .mensaje-error {
            margin-bottom: 16px;
            padding: 12px;
            border: 1px solid #e2a3a3;
            border-radius: 6px;
            color: #8b2020;
            background: #fff0f0;
        }

        .botones { display: flex; justify-content: flex-end; margin-top: 20px; }
        .boton {
            padding: 11px 18px;
            border: 0;
            border-radius: 6px;
            color: #fff;
            background: #1f4e79;
            font-size: 15px;
            cursor: pointer;
        }

        @media (max-width: 480px) {
            body { padding: 14px; }
            .panel { padding: 18px; }
            h1 { font-size: 1.6rem; }
            .botones { justify-content: stretch; }
            .boton { width: 100%; }
        }
    </style>
</head>
<body>

    <main class="contenedor">
        
        <section class="panel">
            <h1>Inicio de sesión</h1>

            @if ($errors->any())
                <div class="mensaje-error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('success'))
                <div class="mensaje-error" role="status" style="border-color:#9bc9a5;color:#245b2d;background:#effaf0;">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('iniciarSesion') }}" method="POST">
                @csrf

                <div class="campo">
                    <label for="usuario">Usuario:</label>
                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        value="{{ old('usuario') }}"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="password">Contraseña:</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <p><a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a></p>

                <div class="botones">
                    <button type="submit" class="boton">Iniciar sesión</button>
                </div>
            </form>
        </section>
    </main>

</body>
</html>

