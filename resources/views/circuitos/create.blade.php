@extends('layouts.app')

@section('titulo', 'Registrar circuito - SIGETA')

@section('content')
    <h1 class="sigeta-titulo mb-2">SIGETA</h1>
    <h2 class="h4 mb-4">Registrar nuevo circuito</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revisa estos datos:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('storeCircuito') }}" method="POST" class="sigeta-panel p-4">
        @csrf

        <div class="mb-3">
            <label for="zona_id" class="form-label">Zona o colonia</label>
            <select id="zona_id" name="zona_id" class="form-select" required>
                <option value="">Selecciona una zona</option>
                @foreach ($zonas as $zona)
                    <option value="{{ $zona->id }}" @selected(old('zona_id') == $zona->id)>
                        {{ $zona->nombre }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">
                El código del circuito se generará automáticamente al guardar.
            </div>
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Calles o descripción</label>
            <input
                type="text"
                id="descripcion"
                name="descripcion"
                class="form-control"
                maxlength="255"
                value="{{ old('descripcion') }}"
                placeholder="Ej. Lauro Ortega y Pípila por las antenas"
            >
        </div>

        <div class="mb-3">
            <label for="duracion" class="form-label">Duración en horas</label>
            <input
                type="number"
                id="duracion"
                name="duracion"
                class="form-control"
                min="1"
                step="1"
                value="{{ old('duracion') }}"
                required
            >
        </div>

        <div class="mb-3">
            <label for="punto_abastecimiento_id" class="form-label">
                Punto de abastecimiento
            </label>
            <select
                id="punto_abastecimiento_id"
                name="punto_abastecimiento_id"
                class="form-select"
            >
                <option value="">Sin punto fijo</option>
                @foreach ($puntos as $punto)
                    <option
                        value="{{ $punto->id }}"
                        @selected(old('punto_abastecimiento_id') == $punto->id)
                    >
                        {{ $punto->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="activo" class="form-label">Estado</label>
            <select id="activo" name="activo" class="form-select" required>
                <option value="1" @selected(old('activo', '1') == '1')>Activo</option>
                <option value="0" @selected(old('activo') == '0')>Inactivo</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Guardar circuito</button>
        <a href="{{ route('circuitos.index') }}" class="btn btn-secondary">Volver</a>
    </form>
@endsection