@extends('layouts.app')

@section('titulo', 'Registrar usuario - SIGETA')

@section('content')
<div class="container py-4" style="max-width:760px;">
    <h1 class="h3 mb-3">Registrar usuario</h1>
    <p class="text-muted">Completa los datos de acceso y asigna un rol.</p>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('usuarios.store') }}" method="POST" class="card card-body">
        @csrf
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" value="{{ old('nombre') }}" required></div>
            <div class="col-md-4"><label class="form-label" for="apellido_paterno">Apellido paterno</label><input class="form-control" id="apellido_paterno" name="apellido_paterno" value="{{ old('apellido_paterno') }}" required></div>
            <div class="col-md-4"><label class="form-label" for="apellido_materno">Apellido materno</label><input class="form-control" id="apellido_materno" name="apellido_materno" value="{{ old('apellido_materno') }}" required></div>
            <div class="col-md-6"><label class="form-label" for="usuario">Nombre de usuario</label><input class="form-control" id="usuario" name="usuario" value="{{ old('usuario') }}" autocomplete="username" required></div>
            <div class="col-md-6"><label class="form-label" for="email">Correo electrónico</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></div>
            <div class="col-md-6"><label class="form-label" for="password">Contraseña</label><input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required></div>
            <div class="col-md-6"><label class="form-label" for="password_confirmation">Confirmar contraseña</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></div>
            <div class="col-md-6"><label class="form-label" for="rol">Rol</label><select class="form-select" id="rol" name="rol" required><option value="">Selecciona un rol</option><option value="administrador" @selected(old('rol') === 'administrador')>Administrador</option><option value="tandeador" @selected(old('rol') === 'tandeador')>Tandeador</option><option value="capturista" @selected(old('rol') === 'capturista')>Capturista</option></select></div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Volver</a>
            <button type="submit" class="btn btn-primary">Registrar usuario</button>
        </div>
    </form>
</div>
@endsection
