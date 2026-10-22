@extends('layouts.app')

@section('titulo', 'Recuperar contraseña - SIGETA')

@section('estilos')
<style>
    body {
        background: linear-gradient(135deg, #cfe5fa 0%, #edf6ff 52%, #d8ebfc 100%);
    }

    .contenido-principal {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tarjeta-recuperacion {
        position: relative;
        overflow: hidden;
        width: 100%;
        max-width: 500px;
        padding: 34px 30px 30px;
        border: 1px solid #d3e2ef;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 20px 48px rgba(38, 92, 139, 0.2);
    }

    .tarjeta-recuperacion::before {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 7px;
        background: #5b9bd5;
        content: "";
    }

    .tarjeta-recuperacion h1 {
        margin: 4px 0 12px;
        color: #24527a;
        font-size: 1.8rem;
        font-weight: 700;
    }

    .tarjeta-recuperacion input:focus {
        border-color: #5b9bd5;
        box-shadow: 0 0 0 3px rgba(91, 155, 213, 0.18);
    }

    .boton-enlace {
        border-color: #3f82bd;
        color: #ffffff;
        background: #3f82bd;
        font-weight: 600;
    }

    .boton-enlace:hover {
        border-color: #2f70aa;
        color: #ffffff;
        background: #2f70aa;
    }

    @media (max-width: 480px) {
        .tarjeta-recuperacion { padding: 30px 20px 22px; }
        .tarjeta-recuperacion .d-flex { gap: 10px; }
    }
</style>
@endsection

@section('content')
<div class="tarjeta-recuperacion">
    <h1>Recuperar contraseña</h1>
    <p class="text-muted">Escribe el correo de tu cuenta. Te enviaremos un enlace temporal para crear una contraseña nueva.</p>

    @if (session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label class="form-label" for="email">Correo electrónico</label>
        <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('login') }}" class="btn btn-outline-secondary">Volver</a>
            <button type="submit" class="btn boton-enlace">Enviar enlace</button>
        </div>
    </form>
</div>
@endsection