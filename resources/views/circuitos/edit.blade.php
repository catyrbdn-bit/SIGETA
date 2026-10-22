@extends('layouts.app')

@section('titulo', 'Editar circuito - SIGETA')

@section('content')
    <h1 class="h4 mb-4">Editar circuito</h1>

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

    <form
        action="{{ route('updateCircuito', $circuito->id) }}"
        method="POST"
        class="sigeta-panel p-4"
    >
        @csrf
        @method('PUT')

        <p class="mb-3">
            Código del circuito:
            <strong>{{ $circuito->nombre }}</strong>
        </p>

        <div class="mb-3">
            <label for="zona_id" class="form-label">Colonia</label>
            <select id="zona_id" name="zona_id" class="form-select" required>
                @foreach ($zonas as $zona)
                    <option
                        value="{{ $zona->id }}"
                        @selected(old('zona_id', $circuito->zona_id) == $zona->id)
                    >
                        {{ $zona->nombre }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">
                Si cambias la colonia, el código del circuito se generará de nuevo.
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
                value="{{ old('descripcion', $circuito->descripcion) }}"
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
                value="{{ old('duracion', $circuito->duracion) }}"
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
                        @selected(old('punto_abastecimiento_id', $circuito->punto_abastecimiento_id) == $punto->id)
                    >
                        {{ $punto->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="activo" class="form-label">Estado</label>
            <select id="activo" name="activo" class="form-select" required>
                <option
                    value="1"
                    @selected(old('activo', $circuito->activo ? '1' : '0') == '1')
                >
                    Activo
                </option>
                <option
                    value="0"
                    @selected(old('activo', $circuito->activo ? '1' : '0') == '0')
                >
                    Inactivo
                </option>
            </select>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('circuitos.index') }}" class="btn btn-secondary" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); return false; }">Volver</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
@endsection


