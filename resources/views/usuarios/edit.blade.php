@extends('layouts.app')

@section('titulo', 'Editar usuario - SIGETA')

@section('content')
<div class="container py-4" style="max-width:760px;">
    <h1 class="h3 mb-3">Editar usuario</h1>
    <p class="text-muted">Si no quieres cambiar la contraseña, deja esos campos vacíos.</p>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST" class="card card-body">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $usuario->nombre) }}" required></div>
            <div class="col-md-4"><label class="form-label" for="apellido_paterno">Apellido paterno</label><input class="form-control" id="apellido_paterno" name="apellido_paterno" value="{{ old('apellido_paterno', $usuario->apellido_paterno) }}" required></div>
            <div class="col-md-4"><label class="form-label" for="apellido_materno">Apellido materno</label><input class="form-control" id="apellido_materno" name="apellido_materno" value="{{ old('apellido_materno', $usuario->apellido_materno) }}" required></div>
            <div class="col-md-6"><label class="form-label" for="usuario">Nombre de usuario</label><input class="form-control" id="usuario" name="usuario" value="{{ old('usuario', $usuario->usuario) }}" autocomplete="username" required></div>
            <div class="col-md-6"><label class="form-label" for="email">Correo electrónico</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" autocomplete="email" required></div>
            <div class="col-md-6"><label class="form-label" for="password">Nueva contraseña</label><input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password"></div>
            <div class="col-md-6"><label class="form-label" for="password_confirmation">Confirmar contraseña</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password"></div>
            <div class="col-md-6"><label class="form-label" for="rol">Rol</label><select class="form-select" id="rol" name="rol" required><option value="administrador" @selected(old('rol', $usuario->rol) === 'administrador')>Administrador</option><option value="tandeador" @selected(old('rol', $usuario->rol) === 'tandeador')>Tandeador</option><option value="capturista" @selected(old('rol', $usuario->rol) === 'capturista')>Capturista</option></select></div>
            <div class="col-md-6"><label class="form-label" for="activo">Estado</label><select class="form-select" id="activo" name="activo" required><option value="1" @selected((string) old('activo', (int) $usuario->activo) === '1')>Activo</option><option value="0" @selected((string) old('activo', (int) $usuario->activo) === '0')>Inactivo</option></select></div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Volver</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection
