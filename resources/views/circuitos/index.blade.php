{{-- vista de index en el modulo circuitos --}}
{{-- archivo del sistema: archivo de codigo del sistema --}}
@extends('layouts.app')

@section('titulo', 'Circuitos registrados - SIGETA')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h4 mb-0">Circuitos registrados</h1>
        </div>
        <a href="{{ route('createCircuito') }}" class="btn btn-primary">+ Nuevo Circuito</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" id="mensaje-exito">
            {{ session('success') }}
        </div>
    @endif

    @if ($circuitos->isEmpty())
        <div class="sigeta-panel p-3">
            Todavía no hay circuitos registrados.
        </div>
    @else
        <div class="table-responsive sigeta-panel">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Circuito</th>
                        <th>Colonia</th>
                        <th>Punto de abastecimiento</th>
                        <th>Lugar / tandeos</th>
                        <th>Duración (horas)</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($circuitos as $circuito)
                        <tr>
                            <td>{{ $circuito->nombre }}</td>
                            <td>{{ $circuito->zona->nombre ?? 'Sin colonia' }}</td>
                            <td>
                                {{ $circuito->puntoAbastecimiento->nombre ?? 'Sin punto fijo registrado' }}
                            </td>
                            <td>
                                {{ $circuito->descripcion ?: 'Sin descripción registrada' }}
                            </td>
                            <td>{{ $circuito->duracion }}</td>
                            <td><span class="estado-badge estado-{{ $circuito->activo ? 'activo' : 'inactivo' }}">{{ $circuito->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="text-nowrap">
                                <a
                                    href="{{ route('editCircuito', $circuito->id) }}"
                                    class="btn btn-sm btn-outline-warning"
                                >
                                    <span class="icono-boton" aria-hidden="true"><svg><use href="#icono-editar"/></svg></span>Editar
                                </a>

                                <form
                                    action="{{ route('deleteCircuito', $circuito->id) }}"
                                    method="POST"
                                    class="d-inline"
                                    data-confirmacion-titulo="Eliminar circuito" data-confirmacion-mensaje="¿Deseas continuar? Si no tiene tandeos asociados, se eliminará definitivamente. Si tiene tandeos asociados, podrás desactivarlo."
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <span class="icono-boton" aria-hidden="true"><svg><use href="#icono-eliminar"/></svg></span>Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <a href="{{ route('dashboard') }}" class="btn btn-secondary mt-3" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); return false; }">
        Volver
    </a>
@endsection



