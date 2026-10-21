@extends('layouts.app')

@section('titulo', 'Cambiar contraseña - SIGETA')

@section('content')
<div class="container py-5" style="max-width:520px;">
    <div class="card card-body">
        <h1 class="h3">Crear contraseña nueva</h1>

        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3">
                <label class="form-label" for="email">Correo electrónico</label>
                <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Nueva contraseña</label>
                <input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">Volver</a>
                <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
            </div>
        </form>
    </div>
</div>
@endsection
