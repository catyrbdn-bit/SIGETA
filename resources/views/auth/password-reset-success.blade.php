<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseña cambiada - SIGETA</title>
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
        .panel {
            position: relative;
            width: 100%;
            max-width: 520px;
            padding: 38px 32px 32px;
            overflow: hidden;
            border: 1px solid #d3e2ef;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 20px 48px rgba(38, 92, 139, .2);
            text-align: center;
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
        h1 { margin: 4px 0 12px; color: #24527a; font-size: 1.8rem; }
        p { margin: 0 0 22px; line-height: 1.55; }
        .boton {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 6px;
            color: #fff;
            background: #3f82bd;
            font-weight: 600;
            text-decoration: none;
        }
        .alternativa { display: block; margin-top: 16px; color: #24527a; }
        .nota { margin-top: 18px; color: #687785; font-size: .9rem; }
    </style>
</head>
<body>
    <main class="panel">
        <h1>Contraseña cambiada</h1>
        <p>Tu contraseña se actualizó correctamente. Abriendo SIGETA para que puedas iniciar sesión con tu nueva contraseña.</p>
        <a class="boton" href="{{ $enlaceAplicacion }}">Volver a SIGETA</a>
        <a class="alternativa" href="{{ route('login') }}">Continuar en el navegador</a>
        <p class="nota">Si el navegador pregunta si deseas abrir SIGETA, acepta la confirmación.</p>
    </main>
    <script>
        window.setTimeout(function () {
            window.location.assign(@json($enlaceAplicacion));
        }, 800);
    </script>
</body>
</html>
