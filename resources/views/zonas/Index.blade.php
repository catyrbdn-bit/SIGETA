@extends('layouts.app')

@section('titulo', 'Colonias - SIGETA')

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

        .aviso-colonia {
            position: fixed;
            inset: 0;
            width: min(680px, calc(100% - 32px));
            height: fit-content;
            max-height: calc(100vh - 40px);
            margin: auto;
            overflow-y: auto;
            padding: 24px;
            border: 1px solid #cbd5df;
            border-radius: 12px;
            color: #243447;
            font-family: inherit;
            box-shadow: 0 18px 50px rgba(20, 40, 60, 0.25);
        }

        .aviso-colonia::backdrop {
            background: rgba(20, 35, 50, 0.55);
        }

        .aviso-colonia h2 {
            margin: 0 0 16px;
            color: #24527a;
        }

        .acciones-aviso {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
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
    
        main.contenido-principal .contenido-vista {
            width: 100%;
            max-width: 100%;
            margin: 0;
        }

        main.contenido-principal .contenido-vista table {
            width: 100%;
            table-layout: fixed;
        }

        main.contenido-principal .contenido-vista th,
        main.contenido-principal .contenido-vista td {
            text-align: center;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        main.contenido-principal .contenido-vista th:nth-child(1) { 
            width: 24%; 
        }
        main.contenido-principal .contenido-vista th:nth-child(2) { 
            width: 30%; 
        }
        main.contenido-principal .contenido-vista th:nth-child(3) {
            width: 18%; 
        }
        main.contenido-principal .contenido-vista th:nth-child(4) { 
            width: 28%; 
        }

        main.contenido-principal .contenido-vista .acciones {
            justify-content: center;
            flex-wrap: wrap;
        }

        @media (max-width: 650px) {
            main.contenido-principal .contenido-vista {
                margin: 16px auto;
                padding: 0 8px;
            }

            main.contenido-principal .contenido-vista table {
                display: table;
                min-width: 720px;
                overflow: visible;
            }
        }
</style>
@endsection

@section('content')
<div class="contenido-vista">
<div class="encabezado-vista">
    <h1>Colonias</h1>
    <a href="{{ route('createZona') }}" class="btn btn-primary">+ Nueva Colonia</a>
</div>
 

    @if (session('success'))
        <div id="mensaje-exito" class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel">
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Colonia</th>
                        <th>Tandeadores asignados</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($zonas as $zona)
                        <tr>
                            <td>{{ $zona->nombre }}</td>
                            <td>{{ $zona->tandeadores->pluck('nombre')->implode(', ') ?: 'Sin tandeadores asignados' }}</td>
                            <td><span class="estado-badge estado-{{ $zona->activo ? 'activo' : 'inactivo' }}">{{ $zona->activo ? 'Activa' : 'Inactiva' }}</span></td>
                            <td>
                                <div class="acciones">
                                    <a href="{{ route('editZona', $zona->id) }}" class="btn btn-sm btn-outline-warning"><span class="icono-boton" aria-hidden="true"><svg><use href="#icono-editar"/></svg></span>Editar</a>

                                    @if ($zona->circuitos_count > 0 || $zona->tandeos_count > 0)
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-abrir-aviso="aviso-colonia-{{ $zona->id }}">
                                            <span class="icono-boton" aria-hidden="true"><svg><use href="#icono-eliminar"/></svg></span>Eliminar
                                        </button>

                                        <dialog id="aviso-colonia-{{ $zona->id }}" class="aviso-colonia">
                                            <h2>¿Qué quieres hacer con esta colonia?</h2>
                                            <p>
                                                Esta colonia tiene {{ $zona->circuitos_count }} circuitos y
                                                {{ $zona->tandeos_count }} tandeos programados asociados.
                                            </p>
                                            <p>
                                                <strong>Desactivarla</strong> conserva la colonia, sus circuitos,
                                                sus tandeos y sus registros. La colonia ya no podrá usarse para
                                                programar nuevos tandeos.
                                            </p>
                                            <p>
                                                <strong>Eliminarla definitivamente</strong> borra también sus
                                                circuitos, tandeos, cumplimientos, repeticiones y reprogramaciones
                                                relacionadas. Esta acción no se puede deshacer.
                                            </p>
                                            <div class="acciones-aviso">
                                                <button type="button" class="btn btn-outline-secondary" data-cerrar-aviso>
                                                    Cancelar
                                                </button>
                                                <form action="{{ route('desactivarZona', $zona->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-primary">Desactivar colonia</button>
                                                </form>
                                                <form action="{{ route('deleteZona', $zona->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="confirmar_eliminacion" value="1">
                                                    <button type="submit" class="btn btn-danger">
                                                        <span class="icono-boton" aria-hidden="true"><svg><use href="#icono-eliminar"/></svg></span>Eliminar definitivamente
                                                    </button>
                                                </form>
                                            </div>
                                        </dialog>
                                    @else
                                        <form action="{{ route('deleteZona', $zona->id) }}" method="POST"
                                              data-confirmacion-titulo="Eliminar colonia"
                                              data-confirmacion-mensaje="¿Eliminar definitivamente esta colonia? Esta acción no se puede deshacer.">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="confirmar_eliminacion" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><span class="icono-boton" aria-hidden="true"><svg><use href="#icono-eliminar"/></svg></span>Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No hay colonias registradas</td>
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

<script>
    document.querySelectorAll('[data-abrir-aviso]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const aviso = document.getElementById(this.dataset.abrirAviso);
            if (aviso) aviso.showModal();
        });
    });

    document.querySelectorAll('[data-cerrar-aviso]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const aviso = this.closest('dialog');
            if (aviso) aviso.close();
        });
    });
</script>
@endsection


