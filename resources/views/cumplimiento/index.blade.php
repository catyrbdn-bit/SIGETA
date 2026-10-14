@extends('layouts.app')

@section('titulo', 'Cumplimiento de tandeos - SIGETA')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-3">Cumplimiento de tandeos de la semana</h1>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <button type="submit" form="form-cumplimientos" class="btn btn-primary">Guardar cambios</button>
        <input type="search" id="buscar-tandeo" class="form-control" style="max-width: 340px" placeholder="Buscar por circuito o zona..." aria-label="Buscar por circuito o zona">
    </div>

    <div class="d-flex gap-2 mb-4">
        <form action="{{ route('reprogramaciones.apagon.previa') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-warning">Apagón general</button>
        </form>
        <button type="button" class="btn btn-outline-secondary" disabled title="Disponible próximamente">Fuga</button>
    </div>

    <form id="form-cumplimientos" action="{{ route('storeCumplimiento') }}" method="POST">
        @csrf
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle" id="tabla-cumplimientos">
                <thead class="table-light">
                    <tr>
                        <th>Circuito / Zona</th>
                        <th>Horario</th>
                        <th>Estado</th>
                        <th>Atraso (H)</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tandeos as $tandeo)
                        @php
                            $registro = $cumplimientos[$tandeo->id] ?? null;
                            $resultado = old("cumplimientos.{$tandeo->id}.resultado", $registro->resultado ?? '');
                            $inicio = \Carbon\Carbon::parse($tandeo->fecha->format('Y-m-d').' '.$tandeo->hora_inicio_programada);
                            $fin = \Carbon\Carbon::parse($tandeo->fecha->format('Y-m-d').' '.$tandeo->hora_fin_programada);
                            if ($fin->lessThanOrEqualTo($inicio)) $fin->addDay();
                            $enCurso = !$registro && now()->betweenIncluded($inicio, $fin);
                            $estadoVisible = $resultado ?: ($enCurso ? 'en_curso' : '');
                            $busqueda = $tandeo->circuito->nombre.' '.$tandeo->circuito->zona->nombre;
                        @endphp
                        <tr class="fila-tandeo" data-busqueda="{{ \Illuminate\Support\Str::lower($busqueda) }}">
                            <td>
                                <strong>{{ $tandeo->circuito->nombre }}</strong><br>
                                <span class="text-muted">{{ $tandeo->circuito->zona->nombre }}</span>
                            </td>
                            <td>{{ $tandeo->fecha->format('d/m/Y') }}<br>{{ \Carbon\Carbon::parse($tandeo->hora_inicio_programada)->format('G:i') }}-{{ \Carbon\Carbon::parse($tandeo->hora_fin_programada)->format('G:i') }}</td>
                            <td>
                                <select class="form-select estado-select" name="cumplimientos[{{ $tandeo->id }}][resultado]" data-atraso="atraso-{{ $tandeo->id }}">
                                    <option value="" {{ $resultado === '' ? 'selected' : '' }}>Sin registrar</option>
                                    @if ($enCurso)
                                        <option value="en_curso" disabled {{ $estadoVisible === 'en_curso' ? 'selected' : '' }}>En curso (automático)</option>
                                    @endif
                                    <option value="cumplido" {{ $resultado === 'cumplido' ? 'selected' : '' }}>Cumplido</option>
                                    <option value="atraso" {{ $resultado === 'atraso' ? 'selected' : '' }}>Con atraso</option>
                                    <option value="no_cumplido" {{ $resultado === 'no_cumplido' ? 'selected' : '' }}>No cumplido</option>
                                </select>
                            </td>
                            <td>
                                <select id="atraso-{{ $tandeo->id }}" class="form-select atraso-select" name="cumplimientos[{{ $tandeo->id }}][horas_atraso]" {{ $resultado === 'atraso' ? '' : 'disabled' }}>
                                    <option value="">—</option>
                                    @for ($hora = 1; $hora <= 24; $hora++)
                                        <option value="{{ $hora }}" {{ (string) old("cumplimientos.{$tandeo->id}.horas_atraso", $registro->horas_atraso ?? '') === (string) $hora ? 'selected' : '' }}>{{ $hora }}</option>
                                    @endfor
                                </select>
                            </td>
                            <td><input class="form-control" type="text" maxlength="1000" name="cumplimientos[{{ $tandeo->id }}][observaciones]" value="{{ old("cumplimientos.{$tandeo->id}.observaciones", $registro->observaciones ?? '') }}" placeholder="Agregar observación..."></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No hay tandeos programados esta semana.</td></tr>
                    @endforelse
                    <tr id="sin-resultados" class="d-none"><td colspan="5" class="text-center text-muted">No se encontraron tandeos con esa búsqueda.</td></tr>
                </tbody>
            </table>
        </div>
    </form>

    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary mt-2">Volver</a>
</div>

<script>
    document.querySelectorAll('.estado-select').forEach(function (select) {
        select.addEventListener('change', function () {
            const atraso = document.getElementById(this.dataset.atraso);
            const aplica = this.value === 'atraso';
            atraso.disabled = !aplica;
            atraso.required = aplica;
            if (!aplica) atraso.value = '';
        });
    });

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
</script>
@endsection
