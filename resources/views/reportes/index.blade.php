@extends('layouts.app')

@section('titulo', 'Reportes')

@section('content')
<div class="contenido-vista">
    <div class="mb-4">
        <h2>Generación de reportes</h2>
        <p class="text-muted mb-0">Elige un reporte y descarga el archivo con los filtros seleccionados.</p>
    </div>

    <form method="GET" action="{{ route('reportes.index') }}">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="h5 mb-3">Filtros</h3>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="zona_id">Colonia</label>
                        <select class="form-select" id="zona_id" name="zona_id">
                            <option value="">Todas las colonias</option>
                            @foreach ($zonas as $zona)
                                <option value="{{ $zona->id }}" @selected((string) $zonaSeleccionada === (string) $zona->id)>
                                    {{ $zona->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="circuito_id">Circuito</label>
                        <select class="form-select" id="circuito_id" name="circuito_id">
                            <option value="">Todos los circuitos</option>
                            @foreach ($circuitos as $circuito)
                                <option value="{{ $circuito->id }}" @selected((string) $circuitoSeleccionado === (string) $circuito->id)>
                                    {{ $circuito->nombre }} ({{ $circuito->zona->nombre }})
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
                        <button type="submit" class="btn btn-outline-primary">Aplicar filtros</button>
                        <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h5">Cumplidos y atrasados por colonia</h3>
                        <p class="text-muted">Incluye comparación gráfica y porcentaje de efectividad.</p>
                        <button type="submit" class="btn btn-primary mt-auto"
                            formaction="{{ route('reportes.pdf', ['tipo' => 'cumplimiento-zona']) }}">
                            Descargar PDF
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h5">Reprogramaciones por motivo</h3>
                        <p class="text-muted">Resume los motivos registrados durante el periodo.</p>
                        <button type="submit" class="btn btn-primary mt-auto"
                            formaction="{{ route('reportes.pdf', ['tipo' => 'reprogramaciones-motivo']) }}">
                            Descargar PDF
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h5">Cumplimiento detallado por circuito</h3>
                        <p class="text-muted">Muestra los tandeos programados, ejecutados y con desfase.</p>
                        <button type="submit" class="btn btn-primary mt-auto"
                            formaction="{{ route('reportes.cumplimiento.circuito.excel') }}">
                            Descargar Excel
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h5">Tandeos pendientes de registrar</h3>
                        <p class="text-muted">Incluye los tandeos terminados que aún no tienen cumplimiento capturado.</p>
                        <button type="submit" class="btn btn-primary mt-auto"
                            formaction="{{ route('reportes.pdf', ['tipo' => 'pendientes']) }}">
                            Descargar PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection