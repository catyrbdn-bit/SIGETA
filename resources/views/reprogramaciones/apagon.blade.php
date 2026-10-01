@extends('layouts.app')

@section('content')
<h2>Apagón general</h2>

@if (session('success'))
    <p>{{ session('success') }}</p>
@endif

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('reprogramaciones.apagon.previa') }}">
    @csrf

    <label for="punto_abastecimiento_id">Punto de abastecimiento</label>
    <select name="punto_abastecimiento_id" id="punto_abastecimiento_id" required>
        <option value="">Selecciona...</option>
        @foreach ($puntos as $punto)
            <option value="{{ $punto->id }}" @selected(old('punto_abastecimiento_id') == $punto->id)>
                {{ $punto->nombre }} (atraso por defecto: {{ $punto->horas_atraso_apagon }} h)
            </option>
        @endforeach
    </select>

    <label for="desde">Desde (fecha y hora del apagón)</label>
    <input type="datetime-local" name="desde" id="desde" value="{{ old('desde', now()->format('Y-m-d\TH:i')) }}" required>

    <label for="horas">Horas de atraso (déjalo vacío para usar el valor por defecto)</label>
    <input type="number" name="horas" id="horas" step="0.5" min="0.5" max="24" value="{{ old('horas') }}">

    <button type="submit">Ver vista previa</button>
    
    <br><br>
    <a href="{{ route('dashboard') }}">Cancelar</a>
</form>
@endsection