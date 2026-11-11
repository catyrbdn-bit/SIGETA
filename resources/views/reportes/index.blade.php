{{-- vista de reportes con filtros y vista previa --}}
{{-- archivo del sistema: pantalla para consultar y descargar reportes --}}
@extends('layouts.app')

@section('titulo', 'Reportes')

@section('content')
<div class="contenido-vista">
    <div class="mb-4">
        <h1 class="titulo-vista">Reportes</h1>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="GET" id="formulario-reportes" data-filtros-persistentes="reportes">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="h5 mb-3">Filtros de consulta</h3>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="zona_id">Colonia</label>
                        <select class="form-select" id="zona_id" name="zona_id">
                            <option value="">Todas las colonias</option>
                            @foreach ($zonas as $zona)
                                <option value="{{ $zona->id }}" @selected((string) $zonaSeleccionada === (string) $zona->id)>{{ $zona->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="circuito_id">Circuito</label>
                        <select class="form-select" id="circuito_id" name="circuito_id">
                            <option value="">Todos los circuitos</option>
                            @foreach ($circuitos as $circuito)
                                <option value="{{ $circuito->id }}" @selected((string) $circuitoSeleccionado === (string) $circuito->id)>{{ $circuito->nombre }} ({{ $circuito->zona->nombre }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="form-label" for="tandeador_id">Tandeador</label>
                        <select class="form-select" id="tandeador_id" name="tandeador_id">
                            <option value="">Selecciona un tandeador</option>
                            @foreach ($tandeadores as $tandeador)
                                <option value="{{ $tandeador->id }}" @selected((string) $tandeadorSeleccionado === (string) $tandeador->id)>
                                    {{ $tandeador->nombre }} {{ $tandeador->apellido_paterno }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="form-label" for="desde">Desde</label>
                        <input class="form-control" type="date" id="desde" name="desde" value="{{ $desde }}">
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="form-label" for="hasta">Hasta</label>
                        <input class="form-control" type="date" id="hasta" name="hasta" value="{{ $hasta }}">
                    </div>
                    <div class="col-12 col-xl-2 d-flex gap-2">
                        <a href="{{ route('reportes.index', ['limpiar' => 1]) }}" class="btn btn-outline-secondary" data-quitar-filtros="reportes">Quitar filtros</a>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="h5 mb-3">Elige el reporte que deseas revisar</h3>
        <div class="row g-3 mb-4">
            @php
                $opcionesReportes = [
                    'cumplimiento-zona' => ['Tandeos cumplidos y atrasados por colonia', 'Compara los resultados y muestra el porcentaje de efectividad.'],
                    'reprogramaciones-motivo' => ['Reprogramaciones por motivo', 'Resume las causas registradas en el periodo.'],
                    'cumplimiento-circuito' => ['Cumplimiento detallado por circuito', 'Muestra tandeos programados, ejecutados y con desfase.'],
                    'pendientes' => ['Tandeos pendientes de registrar', 'Muestra tandeos terminados que aún no tienen cumplimiento.'],
                    'atrasos-colonia' => ['Atrasos por colonia', 'Ordena los tandeos por días de atraso y muestra el motivo.'],
                    'resumen-semanal-tandeador' => ['Resumen semanal por tandeador', 'Selecciona un tandeador para revisar e imprimir únicamente sus horarios de esta semana en PDF.'],
                ];
            @endphp
            @foreach ($opcionesReportes as $valorTipo => $opcion)
                <div class="col-12 col-xl-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h4 class="h6">{{ $opcion[0] }}</h4>
                            <p class="text-muted">{{ $opcion[1] }}</p>
                            <button type="submit" class="btn btn-primary mt-auto"
                                formaction="{{ route('reportes.vista-previa', ['tipo' => $valorTipo]) }}">
                                Ver vista previa
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </form>

    @if (isset($tipoReporte))
        <section class="card" id="vista-previa-reporte">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <h3 class="h4 mb-1">Vista previa: {{ $tituloReporte }}</h3>
                        <p class="text-muted mb-0">Periodo: {{ $periodo }} · Colonia: {{ $zonaReporte }} · Circuito: {{ $circuitoReporte }}</p>
                    </div>
                    @if ($tipoReporte === 'cumplimiento-circuito')
                        <a class="btn btn-primary descargar-reporte"
                            href="{{ route('reportes.cumplimiento.circuito.excel', request()->only(['zona_id', 'circuito_id', 'desde', 'hasta', 'tandeador_id'])) }}"
                            data-nombre="cumplimiento-por-circuito.xlsx">Descargar Excel</a>
                    @else
                        <a class="btn btn-primary descargar-reporte"
                            href="{{ route('reportes.pdf', array_merge(['tipo' => $tipoReporte], request()->only(['zona_id', 'circuito_id', 'desde', 'hasta', 'tandeador_id']))) }}"
                            data-nombre="reporte-{{ $tipoReporte }}.pdf">Descargar PDF</a>
                    @endif
                </div>

                @if ($tipoReporte === 'cumplimiento-zona')
                    @php
                        $cantidadGrafica = array_sum($grafica);
                        $porcentajeCumplido = $cantidadGrafica ? ($grafica['cumplidos'] / $cantidadGrafica) * 100 : 0;
                        $porcentajeAtrasado = $cantidadGrafica ? ($grafica['atrasados'] / $cantidadGrafica) * 100 : 0;
                        $porcentajeNoCumplido = $cantidadGrafica ? ($grafica['no_cumplidos'] / $cantidadGrafica) * 100 : 0;
                        $corteCumplido = $porcentajeCumplido;
                        $corteAtrasado = $porcentajeCumplido + $porcentajeAtrasado;
                    @endphp
                    <div class="d-flex align-items-center flex-wrap gap-4 mb-4 p-3 bg-light rounded">
                        <div aria-label="Gráfica de pastel del cumplimiento" title="Resultados generales del periodo"
                            style="width: 175px; height: 175px; border-radius: 50%; border: 1px solid #d4dce5;
                            background: {{ $cantidadGrafica ? 'conic-gradient(#198754 0% ' . $corteCumplido . '%, #f0ad4e ' . $corteCumplido . '% ' . $corteAtrasado . '%, #dc3545 ' . $corteAtrasado . '% 100%)' : '#dce3e8' }};">
                        </div>
                        <div>
                            <h4 class="h6">Resultados registrados: {{ $cantidadGrafica }}</h4>
                            <p class="mb-1"><span class="badge" style="background:#198754">&nbsp;</span> Cumplidos: {{ $grafica['cumplidos'] }}</p>
                            <p class="mb-1"><span class="badge" style="background:#f0ad4e">&nbsp;</span> Con atraso: {{ $grafica['atrasados'] }}</p>
                            <p class="mb-1"><span class="badge" style="background:#dc3545">&nbsp;</span> No cumplidos: {{ $grafica['no_cumplidos'] }}</p>
                            @if (!$cantidadGrafica)
                                <p class="text-muted mb-0">No hay resultados registrados para mostrar en la gráfica.</p>
                            @endif
                        </div>
                    </div>
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle">
                        <thead><tr><th>Colonia</th><th>Cumplidos</th><th>Con atraso</th><th>No cumplidos</th><th>Total registrado</th><th>Efectividad</th></tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr><td>{{ $fila['zona'] }}</td><td class="text-center">{{ $fila['cumplidos'] }}</td><td class="text-center">{{ $fila['atrasados'] }}</td><td class="text-center">{{ $fila['no_cumplidos'] }}</td><td class="text-center">{{ $fila['total'] }}</td><td class="text-center">{{ number_format($fila['efectividad'], 1) }}%</td></tr>
                            @empty
                                <tr><td colspan="6" class="text-center">No hay cumplimientos para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @elseif ($tipoReporte === 'reprogramaciones-motivo')
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle">
                        <thead><tr><th>Motivo</th><th>Reprogramaciones</th></tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr><td>{{ $fila['motivo'] }}</td><td class="text-center">{{ $fila['cantidad'] }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center">No hay reprogramaciones para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @elseif ($tipoReporte === 'atrasos-colonia')
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle">
                        <thead><tr><th>Colonia</th><th>Tandeo / lugar</th><th>Días de atraso</th><th>Motivo</th></tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr><td>{{ $fila['colonia'] }}</td><td>{{ $fila['tandeo'] }}</td><td class="text-center">{{ $fila['dias'] }}</td><td>{{ $fila['motivo'] }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-center">No hay atrasos para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @elseif ($tipoReporte === 'resumen-semanal-tandeador')
                    <h4 class="h5">Tandeador: {{ $tandeadorReporte->nombre }} {{ $tandeadorReporte->apellido_paterno }} {{ $tandeadorReporte->apellido_materno }}</h4>
                    <p class="text-muted">El PDF contiene únicamente los tandeos de esta persona.</p>
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle text-center">
                        <thead><tr><th>Colonia</th><th>Descripción</th>@foreach ($diasSemana as $dia)<th>{{ $dia['titulo'] }}</th>@endforeach</tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr>
                                    <td>{{ $fila['colonia'] }}</td><td>{{ $fila['calle'] }}</td>
                                    @foreach ($diasSemana as $dia)
                                        <td>@foreach (($fila['horarios'][$dia['fecha']] ?? []) as $horario)<div>{{ $horario }}</div>@endforeach</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ 2 + count($diasSemana) }}" class="text-center">Este tandeador no tiene tandeos asignados esta semana.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @elseif ($tipoReporte === 'cumplimiento-circuito')
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle">
                        <thead><tr><th>Circuito</th><th>Colonia</th><th>Lugar/tandeo</th><th>Programados</th><th>Ejecutados</th><th>Con desfase</th></tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr><td>{{ $fila['circuito'] }}</td><td>{{ $fila['zona'] }}</td><td>{{ $fila['calle'] }}</td><td class="text-center">{{ $fila['programados'] }}</td><td class="text-center">{{ $fila['ejecutados'] }}</td><td class="text-center">{{ $fila['desfasados'] }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="text-center">No hay tandeos para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @else
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle">
                        <thead><tr><th>Circuito</th><th>Colonia</th><th>Lugar/tandeo</th><th>Fecha</th><th>Horario</th></tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr><td>{{ $fila['circuito'] }}</td><td>{{ $fila['zona'] }}</td><td>{{ $fila['calle'] }}</td><td>{{ $fila['fecha'] }}</td><td>{{ $fila['horario'] }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="text-center">No hay tandeos pendientes para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @endif

                <div id="aviso-descarga-reporte" class="alert alert-success mt-3 d-none" role="status"></div>
                <div id="error-descarga-reporte" class="alert alert-danger mt-3 d-none" role="alert"></div>
            </div>
        </section>
    @endif
</div>
@endsection
