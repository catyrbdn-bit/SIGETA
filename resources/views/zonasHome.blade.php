<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Colonias - SIGETA </title>

    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
   

    <h1>Zonas atendidas por SAPUA</h1>

    <h3>Lista de Zonas</h3>

  
    <button>
        <a href="{{ route('createZona') }}">Registrar nueva colonia</a>
    </button>
    <br><br>

    <table>
        <thead>
            <tr>
                <th>Colonia</th>
                <th>Descripción</th>
                <th>Activo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($zonas as $zona) 
                <tr>
                    <td>{{ $zona->nombre }}</td>
                    <td>{{ $zona->descripcion }}</td>
                    <td>{{ $zona->activo ? 'Sí' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
      <br>

    <button>
        volver al panel principal 
        <a href="{{ route('dashboard') }}"></a> 
    </button>

    <form action="{{ route('cerrarSesion') }}" method="POST">
    <br>
        @csrf

        <button type="submit">
            Cerrar sesión
        </button>

    </form>
    <br>

  

</body>
</html>