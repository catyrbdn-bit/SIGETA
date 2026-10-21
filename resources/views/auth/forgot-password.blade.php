@extends('layouts.app')

@section('titulo', 'Recuperar contraseña - SIGETA')

@section('content')
<div class="container py-5" style="max-width:520px;">
    <div class="card card-body">
        <h1 class="h3">Recuperar contraseña</h1>
        <p class="text-muted">Escribe el correo de tu cuenta. Te enviaremos un enlace temporal para crear una contraseña nueva.</p>

        @if (session('status'))<div class="alert alert-info">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label class="form-label" for="email">Correo electrónico</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">Volver</a>
                <button type="submit" class="btn btn-primary">Enviar enlace</button>
            </div>
        </form>
    </div>
</div>
@endsection
