@extends('layouts.app')

@section('titulo', 'Modificar horario - SIGETA')

@section('content')
<div class="container py-4" style="max-width: 760px;">
    <h1 class="h3 mb-3">Modificar horario del tandeo</h1>


    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card mb-3"><div class="card-body">
        <strong>{{ $tandeo->circuito->nombre }} / {{ $tandeo->circuito->zona->nombre }}</strong><br>
        <span class="text-muted">Horario actual: {{ $tandeo->inicio->format('d/m/Y H:i') }} a {{ $tandeo->fin->format('d/m/Y H:i') }}</span>
    </div></div>

    <form action="{{ route('reprogramaciones.manual.previa') }}" method="POST">
        @csrf
        <input type="hidden" name="tandeo_id" value="{{ $tandeo->id }}">
        <div class="mb-3">
            <label for="inicio_nuevo" class="form-label">Nuevo día y hora de inicio</label>
            <input class="form-control" type="datetime-local" id="inicio_nuevo" name="inicio_nuevo" value="{{ old('inicio_nuevo', $tandeo->inicio->format('Y-m-d\TH:i')) }}" required>
        </div>
        <div class="mb-3">
            <label for="motivo" class="form-label">Motivo del cambio</label>
            <input class="form-control" type="text" id="motivo" name="motivo_inicio" maxlength="255" value="{{ old('motivo_inicio', 'Reprogramación manual') }}" required>
        </div>
        <div class="d-flex justify-content-between">
            <a href="{{ route('cumplimiento.index') }}" class="btn btn-outline-secondary">Volver</a>
            <button class="btn btn-primary" type="submit">Ver vista previa</button>
        </div>
    </form>
</div>
@endsection