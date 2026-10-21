@extends('layouts.app')

@section('titulo', 'Circuitos registrados - SIGETA')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="sigeta-titulo mb-2">SIGETA</h1>
            <h2 class="h4 mb-0">Circuitos registrados</h2>
        </div>
        <a href="{{ route('createCircuito') }}" class="btn btn-primary">+ Nuevo Circuito</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" id="mensaje-exito">
            {{ session('success') }}
        </div>
        <!-- js -->
        <script>
            setTimeout(function () {
                const mensaje = document.getElementById('mensaje-exito');

                if (mensaje) {
                    mensaje.style.display = 'none';
                }
            }, 3000);
        </script>
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
                        <th>Calles</th>
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
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Editar
                                </a>

                                <form
                                    action="{{ route('deleteCircuito', $circuito->id) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('¿Desactivar este circuito?');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Desactivar
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


