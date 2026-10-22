@extends('layouts.app')

@section('titulo', 'Calendario de tandeos - SIGETA')

@section('content')
    <h1>Calendario de tandeos</h1>

    <div class="mb-3" style="max-width: 360px;">
        <label for="filtro-colonia" class="form-label">Filtrar por colonia</label>
        <select id="filtro-colonia" class="form-select">
            <option value="">Todas las colonias</option>
            @foreach ($colonias as $colonia)
                <option value="{{ $colonia->id }}">{{ $colonia->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div id="calendario"
         class="mt-3"
         data-eventos-url="{{ route('calendario.eventos') }}"
         data-mover-url="{{ route('calendario.mover') }}"
         data-previa-url="{{ route('reprogramaciones.manual.previa') }}"
         data-csrf="{{ csrf_token() }}"
         data-puede-mover="{{ in_array(auth()->user()->rol, ['administrador', 'capturista']) ? '1' : '0' }}">
    </div>
@endsection