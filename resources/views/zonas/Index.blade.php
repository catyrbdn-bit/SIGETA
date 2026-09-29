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

    @if (session('success'))
    <p id="mensaje-exito" style="color:green;">{{ session('success') }}</p>
    <script>
        setTimeout(function() {
            document.getElementById('mensaje-exito').style.display = 'none';
        }, 3000);
    </script>
    @endif

  
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
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($zonas as $zona) 
                <tr>
                    <td>{{ $zona->nombre }}</td>
                    <td>{{ $zona->descripcion }}</td>
                    <td>{{ $zona->activo ? 'Sí' : 'No' }}</td>
                    <td>
                        <button>
                            <a href="{{ route('editZona', $zona->id) }}">Editar</a>
                        </button>

                        <form action="{{ route('deleteZona', $zona->id) }}" method="POST" style="display:inline;" 
                        onsubmit="return confirm('¿Esta seguro de eliminar la colonia ' + '{{ $zona->nombre }}' + '?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
      <br>

    <button>
        <a href="{{ route('dashboard') }}">
            Volver 
        </a> 
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