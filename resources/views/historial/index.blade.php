@extends('layouts.app')

@section('titulo', 'Historial de actividad - SIGETA')

@section('content')
<div class="contenido-vista historial-vista">
    <h1>Historial de actividad</h1>
    <p>Consulta quién realizó cada acción y cuándo ocurrió.</p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('historial.index') }}" class="card card-body mb-4 historial-filtros">
        <div class="row g-3 align-items-end justify-content-center">
            <div class="col-12 col-md-3">
                <label class="form-label" for="usuario">Usuario</label>
                <select class="form-select" id="usuario" name="usuario">
                    <option value="">Todos los usuarios</option>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario }}" @selected(($filtros['usuario'] ?? '') === $usuario)>{{ $usuario }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="tipo">Tipo de acción</label>
                <select class="form-select" id="tipo" name="tipo">
                    <option value="">Todos los tipos</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo }}" @selected(($filtros['tipo'] ?? '') === $tipo)>{{ ucfirst(str_replace('_', ' ', $tipo)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="desde">Desde</label>
                <input class="form-control" id="desde" name="desde" type="date" value="{{ $filtros['desde'] ?? '' }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="hasta">Hasta</label>
                <input class="form-control" id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] ?? '' }}">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2 justify-content-center">
                <button class="btn btn-primary" type="submit">Buscar</button>
                <a class="btn btn-outline-secondary" href="{{ route('historial.index') }}">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle historial-tabla">
            <thead class="table-light">
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Tipo</th>
                    <th>Acción</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        <td>{{ $registro->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $registro->usuario_nombre ?: 'Usuario eliminado o no identificado' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $registro->tipo)) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $registro->accion)) }}</td>
                        <td>
                            <div>{{ $registro->descripcion }}</div>
                            @if ($registro->detalles)
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="document.getElementById('detalle-historial-{{ $registro->id }}').showModal()">
                                    Ver detalles
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No hay registros que coincidan con los filtros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach ($registros as $registro)
        @if ($registro->detalles)
            <dialog id="detalle-historial-{{ $registro->id }}" class="modal-detalle-historial" aria-labelledby="titulo-historial-{{ $registro->id }}">
                <div class="contenido-modal-historial">
                    <h2 id="titulo-historial-{{ $registro->id }}" class="h5">Detalle de la acción</h2>
                    <p>{{ $registro->descripcion }}</p>

                    @foreach ($registro->detalles as $nombre => $valor)
                        @if ($nombre === 'movimientos' && is_array($valor))
                            <h3 class="h6 mt-3">Tandeos afectados</h3>
                            @foreach ($valor as $movimiento)
                                <div class="movimiento-historial">
                                    <p><strong>Circuito:</strong> {{ $movimiento['circuito'] ?? 'Sin dato' }}</p>
                                    <p><strong>Tandeo:</strong> {{ $movimiento['tandeo_id'] ?? 'Sin dato' }}</p>
                                    <p><strong>Horario anterior:</strong> {{ $movimiento['horario_anterior'] ?? 'Sin dato' }}</p>
                                    <p><strong>Horario nuevo:</strong> {{ $movimiento['horario_nuevo'] ?? 'Sin dato' }}</p>
                                    @if (!empty($movimiento['motivo']))
                                        <p><strong>Motivo:</strong> {{ $movimiento['motivo'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        @elseif (is_array($valor))
                            <div class="detalle-campo-historial">
                                <strong>{{ ucfirst(str_replace('_', ' ', $nombre)) }}</strong>
                                <pre>{{ json_encode($valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @else
                            <p><strong>{{ ucfirst(str_replace('_', ' ', $nombre)) }}:</strong> {{ $valor }}</p>
                        @endif
                    @endforeach

                    <div class="d-flex justify-content-center mt-4">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('detalle-historial-{{ $registro->id }}').close()">Cerrar</button>
                    </div>
                </div>
            </dialog>
        @endif
    @endforeach

    {{ $registros->links() }}

    <style>
        .historial-vista { text-align: center; }
        .historial-filtros .form-label { display: block; text-align: center; }
        .historial-filtros input,
        .historial-filtros select { text-align: center; }
        .historial-tabla th,
        .historial-tabla td { text-align: center; vertical-align: middle; }
        .modal-depuracion,
        .modal-detalle-historial {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            width: min(92vw, 600px);
            max-height: 88vh;
            overflow-y: auto;
            padding: 26px;
            border: 1px solid #d3e2ef;
            border-radius: 16px;
            color: #243447;
            text-align: center;
            box-shadow: 0 20px 48px rgba(38, 92, 139, 0.25);
        }
        .modal-depuracion::backdrop,
        .modal-detalle-historial::backdrop { background: rgba(25, 48, 70, 0.45); }
        .modal-depuracion h2,
        .modal-detalle-historial h2 { color: #24527a; }
        .movimiento-historial,
        .detalle-campo-historial {
            margin-top: 14px;
            padding: 14px;
            border: 1px solid #dce8f2;
            border-radius: 10px;
            background: #f7fbff;
            overflow-wrap: anywhere;
        }
        .movimiento-historial p { margin: 6px 0; }
        .detalle-campo-historial pre {
            margin: 8px 0 0;
            text-align: left;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
        @media (max-width: 480px) {
            .modal-depuracion,
            .modal-detalle-historial { padding: 20px; }
        }
    </style>

    <div class="d-flex justify-content-center mt-4">
        <button type="button" class="btn btn-outline-danger" onclick="document.getElementById('modalDepurar').showModal()">
            Depurar historial antiguo
        </button>
    </div>

    <dialog id="modalDepurar" class="modal-depuracion" aria-labelledby="tituloDepurar">
        <h2 id="tituloDepurar" class="h5">Depurar registros antiguos</h2>
        <p>Solo se eliminarán registros con más de un año. Confirma la acción con tu contraseña. La depuración también quedará anotada en el historial.</p>

        @if ($errors->has('password'))
            <div class="alert alert-danger">{{ $errors->first('password') }}</div>
        @endif

        <form method="POST" action="{{ route('historial.depurar') }}" class="mt-3">
            @csrf
            <label class="form-label" for="passwordDepurar">Contraseña del Administrador</label>
            <input class="form-control" id="passwordDepurar" name="password" type="password" autocomplete="current-password" required autofocus>
            <div class="d-flex justify-content-center gap-2 mt-4">
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('modalDepurar').close()">Cancelar</button>
                <button class="btn btn-danger" type="submit">Confirmar depuración</button>
            </div>
        </form>
    </dialog>

    @if ($errors->has('password'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('modalDepurar').showModal();
            });
        </script>
    @endif
</div>
@endsection