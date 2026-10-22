@extends('layouts.app')

@section('titulo', 'Editar colonia - SIGETA')

@section('estilos')
<style>

        * { box-sizing: border-box; }
.contenido-vista {
            max-width: 800px;
            margin: 32px auto;
            padding: 0 20px;
            font-family: Arial, sans-serif;
            color: #243447;
            background: #f5f7fa;
        }

        h1, h2 { margin-bottom: 8px; }

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

        input, select {
            width: 100%;
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid #b9c4ce;
            border-radius: 6px;
            font-size: 15px;
        }

        .ayuda {
            margin: 7px 0 0;
            color: #657482;
            font-size: 14px;
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
    
</style>
@endsection

@section('content')
<div class="contenido-vista">
<h1>Editar colonia</h1>
    <p class="subtitulo">Actualiza los datos de la colonia seleccionada.</p>

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

    <form action="{{ route('updateZona', $zona->id) }}" method="POST">
        @csrf
        @method('PUT')

        <section class="panel">
            <label for="nombre">Nombre de la colonia:</label>
            <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $zona->nombre) }}" maxlength="100" required>
        </section>

        <section class="panel">
            <label>Tandeadores asignados a esta colonia:</label>
            <p class="ayuda">Puedes seleccionar uno o varios tandeadores.</p>
            @php
                $tandeadoresSeleccionados = array_map(
                    'strval',
                    old('tandeadores', $zona->tandeadores->pluck('id')->all())
                );
            @endphp
            @forelse ($tandeadores as $tandeador)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <input type="checkbox" id="tandeador-{{ $tandeador->id }}" name="tandeadores[]" value="{{ $tandeador->id }}" style="width:18px; min-height:18px;" @checked(in_array((string) $tandeador->id, $tandeadoresSeleccionados, true))>
                    <label for="tandeador-{{ $tandeador->id }}" class="mb-0" style="display:inline;">{{ $tandeador->nombre }} {{ $tandeador->apellido_paterno }}</label>
                </div>
            @empty
                <p class="ayuda mb-0">No hay tandeadores activos registrados.</p>
            @endforelse
        </section>

        <section class="panel">
            <label for="activo">Estado:</label>
            <select id="activo" name="activo" required>
                <option value="1" @selected(old('activo', (string) $zona->activo) == '1')>Activa</option>
                <option value="0" @selected(old('activo', (string) $zona->activo) == '0')>Inactiva</option>
            </select>
        </section>

        <div class="botones">
            <a href="{{ route('zonas.index') }}" class="boton boton-secundario" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); return false; }">Volver a colonias</a>
            <button type="submit" class="boton">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection

