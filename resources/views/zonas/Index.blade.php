@extends('layouts.app')

@section('titulo', 'Colonias atendidas por SAPUA - SIGETA')

@section('estilos')
<style>

        * {
            box-sizing: border-box;
        }
.contenido-vista {
            max-width: 1000px;
            margin: 32px auto;
            padding: 0 20px;
            font-family: Arial, sans-serif;
            color: #243447;
            background: #f5f7fa;
        }

        h1, h2 {
            margin-bottom: 8px;
        }

        .subtitulo {
            margin-top: 0;
            margin-bottom: 24px;
            color: #5d6b78;
        }

        .panel {
            padding: 20px;
            border: 1px solid #dce3ea;
            border-radius: 8px;
            background: #fff;
        }

        .barra-acciones {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e1e7ed;
            text-align: left;
        }

        th {
            background: #eef3f7;
        }

        .boton {
            display: inline-block;
            padding: 9px 14px;
            border: 0;
            border-radius: 6px;
            color: #fff;
            background: #1f4e79;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .boton-secundario {
            color: #243447;
            background: #e1e7ed;
        }

        .boton-peligro {
            background: #a83232;
        }

        .acciones {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mensaje-exito {
            margin-bottom: 16px;
            color: #176b36;
        }

        .volver {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
        }

        @media (max-width: 650px) {
            .barra-acciones {
                align-items: flex-start;
                flex-direction: column;
            }

            .tabla-contenedor {
                overflow-x: auto;
            }

            table {
                min-width: 560px;
            }
        }
    
</style>
@endsection

@section('content')
<div class="contenido-vista">
<h1>SIGETA</h1>
    <h2>Colonias atendidas por SAPUA</h2>
    <p class="subtitulo">Lista de colonias registradas en el sistema.</p>

    @if (session('success'))
        <p id="mensaje-exito" class="mensaje-exito">{{ session('success') }}</p>
        <script>
            setTimeout(function () {
                const mensaje = document.getElementById('mensaje-exito');
                if (mensaje) {
                    mensaje.style.display = 'none';
                }
            }, 3000);
        </script>
    @endif

    <section class="panel">
        <div class="barra-acciones">
            <strong>Lista de colonias</strong>
            <a href="{{ route('createZona') }}" class="boton">Registrar colonia</a>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Colonia</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($zonas as $zona)
                        <tr>
                            <td>{{ $zona->nombre }}</td>
                            <td>{{ $zona->activo ? 'Sí' : 'No' }}</td>
                            <td>
                                <div class="acciones">
                                    <a href="{{ route('editZona', $zona->id) }}" class="boton boton-secundario">Editar</a>

                                    <form action="{{ route('deleteZona', $zona->id) }}" method="POST"
                                          onsubmit="return confirm('¿Está seguro de eliminar esta colonia?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="boton boton-peligro">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No hay colonias registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="volver">
        <a href="{{ route('dashboard') }}" class="boton boton-secundario" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); return false; }">Volver al panel</a>

        
    </div>
</div>
@endsection
