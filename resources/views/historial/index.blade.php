{{-- vista de index en el modulo historial --}}
{{-- archivo del sistema: archivo de codigo del sistema --}}
@extends('layouts.app')

@section('titulo', 'Historial de actividad - SIGETA')

@section('content')
<div class="contenido-vista historial-vista">
    <h1>Historial de actividad</h1>

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

    <form method="GET" action="{{ route('historial.index') }}" class="card card-body mb-4 historial-filtros" data-filtros-persistentes="historial">
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
            <div class="col-12 col-md-auto d-flex gap-2 justify-content-center">
                <button class="btn btn-sm btn-primary" type="submit">Buscar</button>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('historial.index') }}" data-quitar-filtros="historial">Quitar filtros</a>
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

                    @php
                        $antes = $registro->detalles['antes'] ?? [];
                        $despues = $registro->detalles['despues'] ?? [];
                        $cambios = [];
                        if (is_array($antes) && is_array($despues)) {
                            foreach ($despues as $campo => $valorNuevo) {
                                if (!array_key_exists($campo, $antes) || $antes[$campo] !== $valorNuevo) {
                                    $cambios[$campo] = $valorNuevo;
                                }
                            }
                        }
                        $etiquetasCampos = [
                            'nombre' => 'Nombre',
                            'apellido_paterno' => 'Apellido paterno',
                            'apellido_materno' => 'Apellido materno',
                            'usuario' => 'Usuario',
                            'usuario_editado' => 'Usuario editado',
                            'usuario_registrado' => 'Usuario registrado',
                            'usuario_eliminado' => 'Usuario eliminado',
                            'rol' => 'Rol',
                            'activo' => 'Estado',
                            'zona_id' => 'Colonia (identificador)',
                            'circuito_id' => 'Circuito (identificador)',
                            'circuito' => 'Circuito',
                            'descripcion' => 'Lugar/tandeo',
                            'duracion' => 'Duración (horas)',
                            'punto_abastecimiento_id' => 'Punto de abastecimiento (identificador)',
                            'cadena' => 'Cadena de cambios',
                            'tandeo_id' => 'Tandeo',
                            'tandeos_movidos' => 'Tandeos movidos',
                            'tandeos_eliminados' => 'Tandeos eliminados',
                            'fecha' => 'Fecha',
                            'fecha_inicial' => 'Fecha inicial',
                            'hora_inicio' => 'Hora de inicio',
                            'horas_atraso' => 'Horas de atraso',
                            'repeticion_dias' => 'Repetición cada (días)',
                            'repeticion_completa' => 'Repetición completa',
                            'motivo' => 'Motivo',
                            'resultado' => 'Estado del cumplimiento',
                            'observaciones' => 'Observaciones',
                        ];
                        $formatearDato = function ($dato, $campo = null) {
                            if ($dato === null || $dato === '') return 'Sin dato';
                            if ($campo === 'activo' || $campo === 'repeticion_completa') {
                                return $dato ? 'Sí' : 'No';
                            }
                            if ($campo === 'resultado') {
                                return ucfirst(str_replace('_', ' ', (string) $dato));
                            }
                            if (is_bool($dato)) return $dato ? 'Sí' : 'No';
                            return is_scalar($dato) ? (string) $dato : 'Información relacionada';
                        };
                        $movimientos = $registro->detalles['movimientos'] ?? [];
                        $registrosCambios = [];
                        foreach (($registro->detalles['registros'] ?? []) as $fila) {
                            $antesFila = $fila['antes'] ?? [];
                            $despuesFila = $fila['despues'] ?? [];
                            foreach (['resultado', 'horas_atraso', 'motivo', 'observaciones'] as $campoFila) {
                                if (array_key_exists($campoFila, $despuesFila)
                                    && (!array_key_exists($campoFila, $antesFila) || $antesFila[$campoFila] !== $despuesFila[$campoFila])) {
                                    $registrosCambios[] = [
                                        'circuito' => $fila['circuito'] ?? 'Sin dato',
                                        'tandeo' => $fila['tandeo_id'] ?? 'Sin dato',
                                        'campo' => $campoFila,
                                        'antes' => $antesFila[$campoFila] ?? null,
                                        'despues' => $despuesFila[$campoFila],
                                    ];
                                }
                            }
                        }
                    @endphp

                    @php
                        $hayDatosAccion = false;
                        foreach ($registro->detalles as $nombre => $valor) {
                            if (!in_array($nombre, ['antes', 'despues', 'movimientos', 'registros'], true)) {
                                $hayDatosAccion = true;
                                break;
                            }
                        }
                    @endphp
                    @if ($hayDatosAccion)
                        <h3 class="h6 mt-3">Datos de la acción</h3>
                        <div class="table-responsive detalle-campo-historial">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>Dato</th><th>Valor</th></tr></thead>
                                <tbody>
                                    @foreach ($registro->detalles as $nombre => $valor)
                                        @if (in_array($nombre, ['antes', 'despues', 'movimientos', 'registros'], true))
                                            @continue
                                        @endif
                                        @if (is_array($valor))
                                            @foreach ($valor as $campo => $dato)
                                                @if (!is_array($dato))
                                                    <tr>
                                                        <td>{{ is_numeric($campo) ? ucfirst(str_replace('_', ' ', $nombre)) . ' ' . $loop->iteration : ($etiquetasCampos[$campo] ?? ucfirst(str_replace('_', ' ', $campo))) }}</td>
                                                        <td>{{ $formatearDato($dato, $campo) }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @else
                                            <tr>
                                                <td>{{ $etiquetasCampos[$nombre] ?? ucfirst(str_replace('_', ' ', $nombre)) }}</td>
                                                <td>{{ $formatearDato($valor, $nombre) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (!empty($cambios))
                        <h3 class="h6 mt-3">Cambios realizados</h3>
                        <div class="table-responsive detalle-campo-historial">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>Dato</th><th>Antes</th><th>Ahora</th></tr></thead>
                                <tbody>
                                    @foreach ($cambios as $campo => $valorNuevo)
                                        <tr>
                                            <td>{{ is_numeric($campo) ? ucfirst(str_replace('_', ' ', $nombre)) . ' ' . $loop->iteration : ($etiquetasCampos[$campo] ?? ucfirst(str_replace('_', ' ', $campo))) }}</td>
                                            <td>{{ $formatearDato($antes[$campo] ?? null, $campo) }}</td>
                                            <td>{{ $formatearDato($valorNuevo, $campo) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (!empty($movimientos))
                        <h3 class="h6 mt-3">Tandeos afectados</h3>
                        <div class="table-responsive detalle-campo-historial">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Circuito</th><th>Tandeo</th><th>Horario anterior</th><th>Horario nuevo</th><th>Motivo</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($movimientos as $movimiento)
                                        <tr>
                                            <td>{{ $movimiento['circuito'] ?? 'Sin dato' }}</td>
                                            <td>{{ $movimiento['tandeo_id'] ?? 'Sin dato' }}</td>
                                            <td>{{ $movimiento['horario_anterior'] ?? 'Sin dato' }}</td>
                                            <td>{{ $movimiento['horario_nuevo'] ?? 'Sin dato' }}</td>
                                            <td>{{ $movimiento['motivo'] ?? 'Sin dato' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (!empty($registrosCambios))
                        <h3 class="h6 mt-3">Cambios en cumplimientos</h3>
                        <div class="table-responsive detalle-campo-historial">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Circuito</th><th>Tandeo</th><th>Dato</th><th>Antes</th><th>Ahora</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($registrosCambios as $fila)
                                        <tr>
                                            <td>{{ $fila['circuito'] }}</td>
                                            <td>{{ $fila['tandeo'] }}</td>
                                            <td>{{ $etiquetasCampos[$fila['campo']] ?? ucfirst(str_replace('_', ' ', $fila['campo'])) }}</td>
                                            <td>{{ $formatearDato($fila['antes'], $fila['campo']) }}</td>
                                            <td>{{ $formatearDato($fila['despues'], $fila['campo']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <div class="d-flex justify-content-center mt-4">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('detalle-historial-{{ $registro->id }}').close()">Cerrar</button>
                    </div>
                </div>
            </dialog>
        @endif
    @endforeach

    {{ $registros->links() }}

    {{-- estilos visuales de la pantalla --}}
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
        {{-- interacciones de la pantalla --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('modalDepurar').showModal();
            });
        </script>
    @endif
</div>
@endsection
