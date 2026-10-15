@extends('layouts.app')

@section('titulo', 'Editar tandeo - SIGETA')

@section('estilos')
<style>

        * {
            box-sizing: border-box;
        }
.contenido-vista {
            max-width: 800px;
            margin: 32px auto;
            padding: 0 20px;
            font-family: Arial, sans-serif;
            color: #243447;
            background: #f5f7fa;
        }

        h1, h2 {
            margin-bottom: 8px;
        }

        .subtitulo {
            margin-top: 0;
            margin-bottom: 24px;
            color: #5d6b78;
        }

        .panel {
            margin-bottom: 16px;
            padding: 18px;
            border: 1px solid #dce3ea;
            border-radius: 8px;
            background: #fff;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        select, input {
            width: 100%;
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid #b9c4ce;
            border-radius: 6px;
            font-size: 15px;
        }

        .mensaje-error {
            margin-bottom: 18px;
            padding: 12px 16px;
            border: 1px solid #e2a3a3;
            border-radius: 6px;
            color: #8b2020;
            background: #fff0f0;
        }

        .botones {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .boton {
            display: inline-block;
            padding: 11px 18px;
            border: 0;
            border-radius: 6px;
            color: #fff;
            background: #1f4e79;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }

        .boton-secundario {
            color: #243447;
            background: #e1e7ed;
        }

        .ayuda {
            margin-bottom: 0;
            color: #657482;
            font-size: 14px;
        }
    
</style>
@endsection

@section('content')
<div class="contenido-vista">
<h1>SIGETA</h1>
    <h2>Editar tandeo</h2>
    <p class="subtitulo">Modifica los datos del tandeo. Antes de guardar, revisa cómo se acomodarán los horarios afectados.</p>

    @if ($errors->any())
        <div class="mensaje-error">
            <strong>Revisa la información:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('reprogramaciones.manual.previa') }}" method="POST">
        @csrf
        <input type="hidden" name="tandeo_id" value="{{ $tandeo->id }}">

        <section class="panel">
            <label for="circuito_id">Circuito:</label>
            <select id="circuito_id" name="circuito_id" required>
                @foreach ($circuitos as $circuito)
                    <option value="{{ $circuito->id }}" @selected(old('circuito_id', $tandeo->circuito_id) == $circuito->id)>
                        {{ $circuito->nombre }} ({{ $circuito->zona->nombre }})
                    </option>
                @endforeach
            </select>
        </section>

        <section class="panel">
            <label for="fecha_nueva">Fecha:</label>
            <input
                type="date"
                id="fecha_nueva"
                name="fecha_nueva"
                value="{{ old('fecha_nueva', \Carbon\Carbon::parse($tandeo->fecha)->format('Y-m-d')) }}"
                required
            >
        </section>

        <section class="panel">
            <label for="hora_inicio_nueva">Hora de inicio:</label>
            <input
                type="time"
                id="hora_inicio_nueva"
                name="hora_inicio_nueva"
                value="{{ old('hora_inicio_nueva', substr($tandeo->hora_inicio_programada, 0, 5)) }}"
                required
            >
            <p class="ayuda">Usa formato de 24 horas, por ejemplo: 18:30.</p>
        </section>

        <section class="panel">
            <label for="motivo_inicio">Motivo del cambio:</label>
            <input
                type="text"
                id="motivo_inicio"
                name="motivo_inicio"
                maxlength="255"
                value="{{ old('motivo_inicio', 'Reprogramación manual') }}"
                required
            >
        </section>

        <section class="panel">
            <label for="tandeador_id">Tandeador asignado:</label>
            <select id="tandeador_id" name="tandeador_id">
                <option value="">Sin asignar</option>
                @foreach ($tandeadores as $tandeador)
                    <option value="{{ $tandeador->id }}" @selected(old('tandeador_id', $tandeo->tandeador_id) == $tandeador->id)>
                        {{ $tandeador->nombre }}
                    </option>
                @endforeach
            </select>
        </section>

        <div class="botones">
            <a href="{{ route('tandeos.index') }}" class="boton boton-secundario" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); return false; }">Volver</a>
            <button type="submit" class="boton">Ver vista previa</button>
        </div>
    </form>
</div>
@endsection
