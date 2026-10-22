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
            background: linear-gradient(135deg, #cfe5fa 0%, #edf6ff 52%, #d8ebfc 100%);
        }

        .contenedor { width: 100%; max-width: 440px; }
        h1 { margin: 4px 0 24px; color: #24527a; font-size: 1.9rem; font-weight: 700; text-align: center; }
        h2 { margin-top: 0; margin-bottom: 22px; }

        .panel {
            position: relative;
            overflow: hidden;
            padding: 34px 30px 30px;
            border: 1px solid #d3e2ef;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 20px 48px rgba(38, 92, 139, 0.2);
        }

        .panel::before {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 7px;
            background: #5b9bd5;
            content: "";
        }

        .campo { margin-bottom: 18px; }
        label { display: block; margin-bottom: 7px; font-weight: bold; }
        input {
            width: 100%;
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid #b9c4ce;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus {
            outline: none;
            border-color: #5b9bd5;
            box-shadow: 0 0 0 3px rgba(91, 155, 213, 0.18);
        }

        .mensaje-error {
            margin-bottom: 16px;
            padding: 12px;
            border: 1px solid #e2a3a3;
            border-radius: 6px;
            color: #8b2020;
            background: #fff0f0;
        }

        .botones { display: flex; justify-content: stretch; margin-top: 20px; }
        .boton {
            padding: 11px 18px;
            border: 0;
            border-radius: 6px;
            color: #fff;
            background: #3f82bd;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
        }
        .boton:hover { background: #2f70aa; transform: translateY(-1px); }
        .boton:focus-visible { outline: 3px solid rgba(63, 130, 189, 0.3); outline-offset: 3px; }

        @media (max-width: 480px) {
            body { padding: 14px; }
            .panel { padding: 18px; }
            h1 { font-size: 1.6rem; }
            .panel { padding: 30px 20px 22px; }
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

