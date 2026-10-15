<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar colonia - SIGETA</title>

    <style>
        * { box-sizing: border-box; }

        body {
            max-width: 800px;
            margin: 32px auto;
            padding: 0 20px;
            font-family: Arial, sans-serif;
            color: #243447;
            background: #f5f7fa;
        }

        h1, h2 { margin-bottom: 8px; }

        .subtitulo {
            margin-top: 0;
            margin-bottom: 24px;
            color: #5d6b78;
        }

        .panel {
            margin-bottom: 16px;
            padding: 18px;
            border: 1px solid #dce3ea;
            border-radius: 8px;
            background: #fff;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid #b9c4ce;
            border-radius: 6px;
            font-size: 15px;
        }

        .ayuda {
            margin: 7px 0 0;
            color: #657482;
            font-size: 14px;
        }

        .mensaje-error {
            margin-bottom: 18px;
            padding: 12px 16px;
            border: 1px solid #e2a3a3;
            border-radius: 6px;
            color: #8b2020;
            background: #fff0f0;
        }

        .botones {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .boton {
            display: inline-block;
            padding: 11px 18px;
            border: 0;
            border-radius: 6px;
            color: #fff;
            background: #1f4e79;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }

        .boton-secundario {
            color: #243447;
            background: #e1e7ed;
        }
    </style>
</head>
<body>

    <h1>SIGETA</h1>
    <h2>Registrar nueva colonia</h2>
    <p class="subtitulo">Ingresa los datos de la colonia atendida por SAPUA.</p>

    @if ($errors->any())
        <div class="mensaje-error">
            <strong>Revisa la información:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('storeZona') }}" method="POST">
        @csrf

        <section class="panel">
            <label for="nombre">Nombre de la colonia:</label>
            <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" maxlength="100" required>
        </section>

        <section class="panel">
            <label for="activo">Estado:</label>
            <select id="activo" name="activo" required>
                <option value="1" @selected(old('activo', '1') == '1')>Activa</option>
                <option value="0" @selected(old('activo') == '0')>Inactiva</option>
            </select>
        </section>

        <div class="botones">
            <a href="{{ route('zonas.index') }}" class="boton boton-secundario">Volver a colonias</a>
            <button type="submit" class="boton">Registrar colonia</button>
        </div>
    </form>

</body>
</html>





