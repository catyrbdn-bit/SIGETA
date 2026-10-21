@extends('layouts.app')

@section('titulo', 'Gestión de usuarios - SIGETA')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <h1 class="h3 mb-0">Gestión de usuarios</h1>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">Registrar usuario</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr>
                        <td>{{ $usuario->nombre }} {{ $usuario->apellido_paterno }} {{ $usuario->apellido_materno }}</td>
                        <td>{{ $usuario->usuario }}</td>
                        <td>{{ $usuario->email }}</td>
                        <td>{{ ucfirst($usuario->rol) }}</td>
                        <td><span class="estado-badge estado-{{ $usuario->activo ? 'activo' : 'inactivo' }}">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" onsubmit="return confirm('¿Eliminar a este usuario? Sus registros de historial se conservarán, pero ya no mostrarán quién los realizó.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No hay usuarios registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary mt-4">Volver</a>
</div>
@endsection
