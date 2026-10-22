@extends('layouts.app')

@section('titulo', 'Panel principal - SIGETA')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <h1 class="mb-0">Panel principal</h1>
    <div class="d-flex flex-wrap gap-2">
        @if (Auth::user()->rol !== 'tandeador')
            <a href="{{ route('createTandeo') }}" class="btn btn-primary">+ Nuevo Tandeo</a>
        @endif
        <a href="{{ route('tandeos.index') }}" class="btn btn-outline-primary">Consultar tandeos</a>
    </div>
</div>

<div class="row g-3 align-items-end mb-4">
    <form action="{{ route('dashboard') }}" method="GET" class="col-12 col-xl-5 d-flex flex-wrap align-items-end gap-2">
        <div style="min-width: 155px; flex: 1;">
            <label for="mes_num" class="form-label">Mes</label>
            <select id="mes_num" name="mes_num" class="form-select">
                @php
                    $nombresMeses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                @endphp
                @foreach ($nombresMeses as $numero => $nombreMes)
                    <option value="{{ $numero }}" @selected($mesNumero === $numero)>{{ $nombreMes }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 105px;">
            <label for="anio" class="form-label">Año</label>
            <select id="anio" name="anio" class="form-select">
                @for ($anio = $anioSeleccionado - 5; $anio <= $anioSeleccionado + 5; $anio++)
                    <option value="{{ $anio }}" @selected($anioSeleccionado === $anio)>{{ $anio }}</option>
                @endfor
            </select>
        </div>
        <button type="submit" class="btn btn-outline-primary">Consultar</button>
    </form>

    <form action="{{ route('dashboard') }}" method="GET" class="col-12 col-md-7 col-xl-4">
        <label for="buscar-tandeo" class="form-label">Buscar circuito, colonia o calle</label>
        <div class="input-group">
            <input type="search" id="buscar-tandeo" name="buscar" class="form-control"
                   value="{{ $busqueda }}" placeholder="Escribe para buscar...">
            <button type="submit" class="btn btn-outline-primary">Buscar</button>
        </div>
     
    </form>
    <div class="col-12 col-md-5 col-xl-3">
        <label for="filtro-estado" class="form-label">Filtrar por estado</label>
        <select id="filtro-estado" class="form-select">
            <option value="todos">Todos los estados</option>
            <option value="programado">Programado</option>
            <option value="en_curso">En curso</option>
            <option value="pendiente">Pendiente de registrar</option>
            <option value="cumplido">Cumplido</option>
            <option value="atraso">Con atraso</option>
            <option value="no_cumplido">No cumplido</option>
        </select>
    </div>
    <div class="col-12">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Quitar filtros</a>
    </div>
</div>

<h2 class="h5 mb-3">
    @if ($busqueda !== '')
        Resultados para "{{ $busqueda }}" 
    @else
        Tandeos de {{ $fechaMes->locale('es')->translatedFormat('F Y') }}
    @endif
</h2>
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle" id="tabla-tandeos">
        <thead class="table-light">
            <tr>
                <th>Circuito / Colonia</th>
                <th>Calles</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Estado</th>
                @if (Auth::user()->rol !== 'tandeador')
                    <th>Tandeador</th>
                    <th>Acciones</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($tandeosMes as $tandeo)
                @php
                    $cumplimiento = $tandeo->cumplimientos->first();
                    if ($cumplimiento && $cumplimiento->fecha_captura && \Carbon\Carbon::parse($cumplimiento->fecha_captura)->lt($tandeo->inicio)) {
                        $cumplimiento = null;
                    }
                    if (now()->lt($tandeo->inicio)) {
                        $estado = 'programado';
                    } elseif ($tandeo->estado !== 'programado') {
                        $estado = $tandeo->estado;
                    } elseif ($cumplimiento) {
                        $estado = $cumplimiento->resultado;
                    } elseif (now()->betweenIncluded($tandeo->inicio, $tandeo->fin)) {
                        $estado = 'en_curso';
                    } else {
                        $estado = 'pendiente';
                    }

                    $nombresEstados = [
                        'programado' => 'Programado',
                        'en_curso' => 'En curso',
                        'pendiente' => 'Pendiente de registrar',
                        'cumplido' => 'Cumplido',
                        'atraso' => 'Con atraso',
                        'no_cumplido' => 'No cumplido',
                    ];
                @endphp
                <tr class="fila-tandeo"
                    data-estado="{{ $estado }}"
                    data-busqueda="{{ strtolower($tandeo->circuito->nombre . ' ' . $tandeo->circuito->zona->nombre . ' ' . ($tandeo->circuito->descripcion ?? '')) }}">
                    <td>
                        <strong>{{ $tandeo->circuito->nombre }}</strong><br>
                        <span class="text-muted">{{ $tandeo->circuito->zona->nombre }}</span>
                    </td>
                    <td>{{ $tandeo->circuito->descripcion ?: 'Sin calles registradas' }}</td>
                    <td>{{ $tandeo->fecha->locale('es')->translatedFormat('l d/M/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($tandeo->hora_inicio_programada)->format('G:i') }} - {{ \Carbon\Carbon::parse($tandeo->hora_fin_programada)->format('G:i') }}</td>
                    <td><span class="estado-badge estado-{{ $estado }}">{{ $nombresEstados[$estado] ?? ucfirst(str_replace('_', ' ', $estado)) }}</span></td>
                    @if (Auth::user()->rol !== 'tandeador')
                        <td>{{ $tandeo->tandeadores->pluck('nombre')->implode(', ') ?: 'Sin asignar' }}</td>
                        <td><div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('editTandeo', $tandeo->id) }}" class="btn btn-sm btn-outline-primary">Reprogramar horario</a>
                            <a href="{{ route('cumplimiento.index', ['tandeo_id' => $tandeo->id]) }}" class="btn btn-sm btn-outline-secondary">Registrar cumplimiento</a></div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ Auth::user()->rol === 'tandeador' ? 5 : 7 }}" class="text-center text-muted">{{ $busqueda !== '' ? 'No se encontraron tandeos para esta busqueda.' : (Auth::user()->rol === 'tandeador' ? 'No tienes tandeos asignados este mes.' : 'No hay tandeos programados este mes.') }}</td></tr>
            @endforelse
            <tr id="sin-tandeos-filtrados" class="d-none"><td colspan="{{ Auth::user()->rol === 'tandeador' ? 5 : 7 }}" class="text-center text-muted">No hay tandeos con esos filtros.</td></tr>
        </tbody>
    </table>
</div>




<script>
    const filtroEstado = document.getElementById('filtro-estado');
    const buscarTandeo = document.getElementById('buscar-tandeo');
    const filasTandeos = document.querySelectorAll('.fila-tandeo');
    const avisoSinResultados = document.getElementById('sin-tandeos-filtrados');

    function normalizarTexto(texto) {
        return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function filtrarTandeos() {
        const estadoElegido = filtroEstado.value;
        const textoBuscado = normalizarTexto(buscarTandeo.value.trim());
        let cantidadVisible = 0;

        filasTandeos.forEach(function (fila) {
            const coincideEstado = estadoElegido === 'todos' || fila.dataset.estado === estadoElegido;
            const coincideTexto = normalizarTexto(fila.dataset.busqueda).includes(textoBuscado);
            const mostrar = coincideEstado && coincideTexto;

            fila.classList.toggle('d-none', !mostrar);
            if (mostrar) cantidadVisible++;
        });

        avisoSinResultados.classList.toggle('d-none', cantidadVisible > 0);
    }

    filtroEstado.addEventListener('change', filtrarTandeos);
    buscarTandeo.addEventListener('input', filtrarTandeos);
</script>
@endsection














