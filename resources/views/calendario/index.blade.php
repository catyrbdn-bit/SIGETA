@extends('layouts.app')

@section('titulo', 'Calendario de tandeos - SIGETA')

@section('content')
    <h1>Calendario de tandeos</h1>

    <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
        <div style="max-width: 360px; flex: 1;">
            <label for="filtro-colonia" class="form-label">Filtrar por colonia</label>
            <select id="filtro-colonia" class="form-select">
                <option value="">Todas las colonias</option>
                @foreach ($colonias as $colonia)
                    <option value="{{ $colonia->id }}">{{ $colonia->nombre }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" id="limpiar-filtro-calendario" class="btn btn-outline-secondary">Quitar filtro</button>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Estados de los tandeos">
        <span class="leyenda estado-programado">Programado</span>
        <span class="leyenda estado-en-curso">En curso</span>
        <span class="leyenda estado-pendiente">Pendiente de registrar</span>
        <span class="leyenda estado-cumplido">Cumplido</span>
        <span class="leyenda estado-atraso">Con atraso</span>
        <span class="leyenda estado-no-cumplido">No cumplido</span>
    </div>

    <div id="calendario"
         class="mt-3"
         data-eventos-url="{{ route('calendario.eventos') }}"
         data-mover-url="{{ route('calendario.mover') }}"
         data-csrf="{{ csrf_token() }}"
         data-puede-mover="{{ in_array(auth()->user()->rol, ['administrador', 'capturista']) ? '1' : '0' }}">
    </div>

    <style>
        #calendario .cal-evento {
            display: flex;
            flex-direction: column;
            gap: 2px;
            line-height: 1.2;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        #calendario .cal-evento-cabecera,
        #calendario .cal-evento-calle {
            white-space: normal;
            overflow-wrap: anywhere;
        }

        #calendario .cal-evento-cabecera { font-weight: 700; }
        #calendario .cal-evento-horario { font-size: .9em; font-weight: 600; white-space: nowrap; }

        #calendario .fc-event.estado-programado, .leyenda.estado-programado { background-color: #dbeafe; border-color: #60a5fa; color: #174ea6; }
        #calendario .fc-event.estado-en-curso, .leyenda.estado-en-curso { background-color: #fff3cd; border-color: #e6b800; color: #735c00; }
        #calendario .fc-event.estado-pendiente, .leyenda.estado-pendiente { background-color: #e5e7eb; border-color: #9ca3af; color: #374151; }
        #calendario .fc-event.estado-cumplido, .leyenda.estado-cumplido { background-color: #d1e7dd; border-color: #75b798; color: #146c43; }
        #calendario .fc-event.estado-atraso, .leyenda.estado-atraso { background-color: #ffe2c6; border-color: #fd9843; color: #8a3d00; }
        #calendario .fc-event.estado-no-cumplido, .leyenda.estado-no-cumplido { background-color: #f8d7da; border-color: #ea868f; color: #842029; }

        .leyenda {
            display: inline-block;
            border: 1px solid;
            border-radius: 5px;
            padding: 4px 9px;
            font-size: .9rem;
        }

        .detalle-tandeo, .confirmar-movimiento {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            width: min(520px, calc(100% - 32px));
            max-height: 88vh;
            overflow-y: auto;
            border: 0;
            border-radius: 10px;
            padding: 0;
            text-align: center;
            box-shadow: 0 12px 36px #0004;
        }

        .detalle-tandeo::backdrop, .confirmar-movimiento::backdrop { background: #0007; }
        .dialogo-contenido { padding: 24px; text-align: center; }
        .dialogo-contenido h2 { margin: 0 0 18px; }
        .dialogo-contenido p { margin: 8px 0; overflow-wrap: anywhere; }
        .detalle-tandeo .dialogo-contenido p,
        .confirmar-movimiento .dialogo-contenido p { text-align: left; }
        .dialogo-acciones { display: flex; justify-content: center; gap: 10px; margin-top: 22px; }
        .dialogo-error { margin-top: 14px; }
    </style>

    <dialog id="detalle-tandeo" class="detalle-tandeo">
        <div class="dialogo-contenido">
            <h2>Detalle del tandeo</h2>
            <p><strong>Circuito:</strong> <span id="detalle-circuito"></span></p>
            <p><strong>Colonia:</strong> <span id="detalle-colonia"></span></p>
            <p><strong>Calles:</strong> <span id="detalle-calles"></span></p>
            <p><strong>Fecha:</strong> <span id="detalle-fecha"></span></p>
            <p><strong>Horario:</strong> <span id="detalle-horario"></span></p>
            <p><strong>Estado:</strong> <span id="detalle-estado"></span></p>
            <p><strong>Tandeador:</strong> <span id="detalle-tandeadores"></span></p>
            <div class="dialogo-acciones">
                <button type="button" id="cerrar-detalle-tandeo" class="btn btn-primary">Cerrar</button>
            </div>
        </div>
    </dialog>

    <dialog id="confirmar-movimiento" class="confirmar-movimiento">
        <div class="dialogo-contenido">
            <h2>Confirmar cambio de horario</h2>
            <p><strong>Circuito:</strong> <span id="cambio-circuito"></span></p>
            <p><strong>Colonia:</strong> <span id="cambio-colonia"></span></p>
            <p><strong>Calles:</strong> <span id="cambio-calles"></span></p>
            <p><strong>Horario actual:</strong> <span id="cambio-actual"></span></p>
            <p><strong>Horario propuesto:</strong> <span id="cambio-propuesto"></span></p>
            <div class="mb-3 text-start">
                <label for="cambio-hora" class="form-label"><strong>Ajustar hora de inicio</strong></label>
                <input type="time" id="cambio-hora" class="form-control" required>
                <small class="text-muted">Se conserva la fecha elegida y la duración del tandeo.</small>
            </div>
            <div id="error-empalme" class="alert alert-danger dialogo-error" role="alert" hidden></div>
            <div class="dialogo-acciones">
                <button type="button" id="cancelar-movimiento" class="btn btn-outline-secondary">Cancelar</button>
                <button type="button" id="confirmar-movimiento-boton" class="btn btn-primary">Confirmar cambio</button>
            </div>
        </div>
    </dialog>
@endsection