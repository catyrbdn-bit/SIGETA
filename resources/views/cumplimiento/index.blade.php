@extends('layouts.app')

@section('titulo', 'Cumplimiento de tandeos - SIGETA')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-3">Cumplimiento de tandeos </h1>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif


 

    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <input type="search" id="buscar-tandeo" class="form-control" style="max-width: 340px" placeholder="Buscar por circuito, colonia o calle..." aria-label="Buscar por circuito, colonia o calle">
                <button type="button" id="limpiar-busqueda-cumplimiento" class="btn btn-outline-secondary">Quitar filtro</button>
                <form action="{{ route('reprogramaciones.apagon.previa') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-warning">Apagon general</button>
        </form>
        <form action="{{ route('reprogramaciones.fuga.previa') }}" method="POST">
            @csrf
            <input type="hidden" name="tipo_fuga" value="laguna">
            <input type="hidden" name="horas_atraso" value="6">
            <button type="submit" class="btn btn-outline-info">Fuga laguna</button>
        </form>
        <form action="{{ route('reprogramaciones.fuga.previa') }}" method="POST">
            @csrf
            <input type="hidden" name="tipo_fuga" value="pozo">
            <input type="hidden" name="horas_atraso" value="4">
            <button type="submit" class="btn btn-outline-info">Fuga pozo</button>
        </form>
   
        <button type="submit" form="form-cumplimientos" class="btn btn-primary">Guardar cambios</button>
    </div>

    <form id="form-cumplimientos" action="{{ route('storeCumplimiento') }}" method="POST">
        @csrf
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle" id="tabla-cumplimientos">
                <thead class="table-light">
                    <tr>
                        <th>Circuito / Colonia</th>
                        <th>Calles</th>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th>Estado</th>
                        <th>Atraso (H)</th>
                        <th>Observaciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tandeos as $tandeo)
                        @php
                            // Buscamos el cumplimiento guardado para este tandeo
                            $registro = $cumplimientos[$tandeo->id] ?? null;
                            $resultado = old("cumplimientos.{$tandeo->id}.resultado", $registro->resultado ?? '');

                            // El tandeo ya conoce su fecha completa de inicio y fin
                            $inicio = $tandeo->inicio;
                            $fin = $tandeo->fin;

                            // Conservamos el atraso registrado desde Editar aunque el nuevo horario sea futuro.
                            $registroAtrasoManual = $registro
                                && $registro->resultado === 'atraso'
                                && $registro->motivo === 'otro';

                            // Ignoramos un cumplimiento viejo capturado antes del horario actual.
                            if ($registro && $registro->fecha_captura && \Carbon\Carbon::parse($registro->fecha_captura)->lt($inicio) && !$registroAtrasoManual) {
                                $registro = null;
                                $resultado = '';
                            }

                            $esFuturo = now()->lt($inicio) && !$registroAtrasoManual;
                            $enCurso = now()->betweenIncluded($inicio, $fin);
                            $estadoActual = $esFuturo
                                ? 'programado'
                                : (($enCurso && $resultado === '') ? 'en_curso' : ($resultado ?: 'pendiente'));

                            // Un tandeo futuro todavía no puede tener cumplimiento
                            if ($esFuturo) {
                                $resultado = '';
                            }

                            $busqueda = $tandeo->circuito->nombre.' '.$tandeo->circuito->zona->nombre.' '.$tandeo->circuito->descripcion;
                        @endphp
                        <tr class="fila-tandeo" id="tandeo-{{ $tandeo->id }}" data-busqueda="{{ \Illuminate\Support\Str::lower($busqueda) }}">
                            <td>
                                <strong>{{ $tandeo->circuito->nombre }}</strong><br>
                                <span class="text-muted">{{ $tandeo->circuito->zona->nombre }}</span>
                            </td>
                            <td>{{ $tandeo->circuito->descripcion ?: 'Sin calles registradas' }}</td>
                            <td>{{ $tandeo->fecha->locale('es')->translatedFormat('l d/M/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($tandeo->hora_inicio_programada)->format('G:i') }} - {{ \Carbon\Carbon::parse($tandeo->hora_fin_programada)->format('G:i') }}</td>
                            <td>
                                <select id="estado-{{ $tandeo->id }}" class="form-select estado-select estado-{{ $estadoActual }}" name="cumplimientos[{{ $tandeo->id }}][resultado]" data-automatico="{{ $esFuturo ? 'programado' : ($enCurso ? 'en_curso' : 'pendiente') }}" data-atraso="atraso-{{ $tandeo->id }}">
                                    @if ($esFuturo)
                                        <option value="" selected disabled>Programado</option>
                                    @elseif ($enCurso && $resultado === '')
                                        <option value="" selected disabled>En curso</option>
                                    @else
                                        <option value="" {{ $resultado === '' ? 'selected' : '' }}>Sin registrar</option>
                                    @endif
                                    <option value="cumplido" @disabled($esFuturo) {{ $resultado === 'cumplido' ? 'selected' : '' }}>Cumplido</option>
                                    <option value="atraso" @disabled($esFuturo) {{ $resultado === 'atraso' ? 'selected' : '' }}>Con atraso</option>
                                    <option value="no_cumplido" @disabled($esFuturo) {{ $resultado === 'no_cumplido' ? 'selected' : '' }}>No cumplido</option>
                                </select>
                            </td>
                            <td>
                                <select id="atraso-{{ $tandeo->id }}" class="form-select atraso-select" name="cumplimientos[{{ $tandeo->id }}][horas_atraso]" data-estado="estado-{{ $tandeo->id }}" {{ $esFuturo ? 'disabled' : '' }}>
                                    <option value="">—</option>
                                    @for ($hora = 1; $hora <= 24; $hora++)
                                        <option value="{{ $hora }}" {{ (string) old("cumplimientos.{$tandeo->id}.horas_atraso", $registro->horas_atraso ?? '') === (string) $hora ? 'selected' : '' }}>{{ $hora }}</option>
                                    @endfor
                                </select>
                            
                                @if ($esFuturo)
                                    <small class="text-muted d-block mt-1"></small>
                                @else
                                    <small class="text-muted d-block mt-1">Elegir horas</small>
                                @endif
                            </td>
                            <td><input class="form-control" type="text" maxlength="1000" name="cumplimientos[{{ $tandeo->id }}][observaciones]" value="{{ old("cumplimientos.{$tandeo->id}.observaciones", $registro->observaciones ?? '') }}" placeholder="Agregar observación..."></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('editTandeo', $tandeo->id) }}">Editar tandeo</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No hay tandeos programados esta semana</td></tr>
                    @endforelse
                    <tr id="sin-resultados" class="d-none"><td colspan="8" class="text-center text-muted">No se encontraron tandeos con esa búsqueda</td></tr>
                </tbody>
            </table>
        </div>
    </form>

    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary mt-2" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); return false; }">Volver</a>
</div>




<script>
    document.querySelectorAll('.estado-select').forEach(function (select) {
        const estadosConColor = ['programado', 'en_curso', 'pendiente', 'cumplido', 'atraso', 'no_cumplido'];

        function actualizarColor() {
            estadosConColor.forEach(function (estado) {
                select.classList.remove('estado-' + estado);
            });
            select.classList.add('estado-' + (select.value || select.dataset.automatico));
        }

        actualizarColor();
        select.addEventListener('change', function () {
            actualizarColor();
            const atraso = document.getElementById(this.dataset.atraso);
            const aplica = this.value === 'atraso';
            atraso.required = aplica;
            if (!aplica) atraso.value = '';
        });
    });

    document.querySelectorAll('.atraso-select').forEach(function (select) {
        select.addEventListener('change', function () {
            const estado = document.getElementById(this.dataset.estado);

            if (this.value !== '') {
                estado.value = 'atraso';
                estado.dispatchEvent(new Event('change'));
            } else if (estado.value === 'atraso') {
                estado.value = '';
                estado.dispatchEvent(new Event('change'));
            }
        });
    });

    const tandeoSeleccionado = new URLSearchParams(window.location.search).get('tandeo_id');
    if (tandeoSeleccionado) {
        const filaSeleccionada = document.getElementById('tandeo-' + tandeoSeleccionado);
        if (filaSeleccionada) {
            filaSeleccionada.classList.add('table-warning');
            filaSeleccionada.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    const buscador = document.getElementById('buscar-tandeo');
    buscador.addEventListener('input', function () {
        const texto = this.value.trim().toLocaleLowerCase('es');
        let visibles = 0;
        document.querySelectorAll('.fila-tandeo').forEach(function (fila) {
            const coincide = fila.dataset.busqueda.includes(texto);
            fila.classList.toggle('d-none', !coincide);
            if (coincide) visibles++;
        });
        document.getElementById('sin-resultados').classList.toggle('d-none', visibles > 0 || texto === '');
    });

    document.getElementById('limpiar-busqueda-cumplimiento').addEventListener('click', function () {
        buscador.value = '';
        buscador.dispatchEvent(new Event('input'));
    });
</script>
@endsection
