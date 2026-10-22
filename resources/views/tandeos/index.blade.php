@extends('layouts.app')

@section('titulo', 'Tandeos programados - SIGETA')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">{{ Auth::user()->rol === 'tandeador' ? 'Mis tandeos asignados' : 'Tandeos programados' }}</h1>
    @if (Auth::user()->rol !== 'tandeador')
        <a href="{{ route('createTandeo') }}" class="btn btn-primary">+ Nuevo Tandeo</a>
    @endif
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
        <label for="buscar-tandeo" class="form-label">Circuito, colonia o calle</label>
        <input type="search" id="buscar-tandeo" class="form-control" placeholder="Escribe para buscar circuito, colonia o calle">
    </div>
    <div class="col-md-3">
        <label for="filtro-zona" class="form-label">Colonia</label>
        <select id="filtro-zona" class="form-select">
            <option value="todas">Todas las colonias</option>
            @foreach ($zonas as $zona)
                <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label for="fecha-desde" class="form-label">Desde</label>
        <input type="date" id="fecha-desde" class="form-control">
    </div>
    <div class="col-md-2">
        <label for="fecha-hasta" class="form-label">Hasta</label>
        <input type="date" id="fecha-hasta" class="form-control">
    </div>
    <div class="col-md-3">
        <label for="filtro-estado" class="form-label">Estado</label>
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
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle" id="tabla-tandeos">
        <thead class="table-light">
            <tr>
                <th>Circuito</th>
                <th>Colonia</th>
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
            @forelse ($tandeos as $tandeo)
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
                    $textoBusqueda = \Illuminate\Support\Str::lower($tandeo->circuito->nombre.' '.$tandeo->circuito->zona->nombre.' '.$tandeo->circuito->descripcion);
                @endphp
                <tr class="fila-tandeo"
                    data-busqueda="{{ $textoBusqueda }}"
                    data-zona="{{ $tandeo->circuito->zona_id }}"
                    data-fecha="{{ $tandeo->fecha->format('Y-m-d') }}"
                    data-estado="{{ $estado }}">
                    <td>{{ $tandeo->circuito->nombre }}</td>
                    <td>{{ $tandeo->circuito->zona->nombre }}</td>
                    <td>{{ $tandeo->circuito->descripcion ?: 'Sin calles registradas' }}</td>
                    <td>{{ $tandeo->fecha->locale('es')->translatedFormat('l d/M/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($tandeo->hora_inicio_programada)->format('G:i') }} - {{ \Carbon\Carbon::parse($tandeo->hora_fin_programada)->format('G:i') }}</td>
                    <td><span class="estado-badge estado-{{ $estado }}">{{ $nombresEstados[$estado] ?? ucfirst(str_replace('_', ' ', $estado)) }}</span></td>
                    @if (Auth::user()->rol !== 'tandeador')
                        <td>{{ $tandeo->tandeador->nombre ?? 'Sin asignar' }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('editTandeo', $tandeo->id) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        
                        <form action="{{ route('deleteTandeo', $tandeo->id) }}" method="POST" class="d-inline" onsubmit="return confirm(@js($tandeo->repeticion_uuid ? 'Se eliminarán todos los tandeos de esta repetición y su historial. Si solo quieres cambiar fechas, usa Editar. ¿Continuar?' : '¿Estás seguro de eliminar este tandeo? Esta acción no se puede deshacer.'))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ Auth::user()->rol === 'tandeador' ? 6 : 8 }}" class="text-center text-muted">{{ Auth::user()->rol === 'tandeador' ? 'No tienes tandeos asignados.' : 'No hay tandeos registrados.' }}</td></tr>
            @endforelse
            <tr id="sin-resultados" class="d-none"><td colspan="{{ Auth::user()->rol === 'tandeador' ? 6 : 8 }}" class="text-center text-muted">No se encontraron tandeos con esos filtros.</td></tr>
        </tbody>
    </table>
</div>

<a href="{{ route('dashboard') }}" class="btn btn-outline-secondary" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); return false; }">Volver</a>

<script>
    function filtrarTandeos() {
        const texto = document.getElementById('buscar-tandeo').value.trim().toLocaleLowerCase('es');
        const zona = document.getElementById('filtro-zona').value;
        const estado = document.getElementById('filtro-estado').value;
        const desde = document.getElementById('fecha-desde').value;
        const hasta = document.getElementById('fecha-hasta').value;
        let visibles = 0;

        document.querySelectorAll('.fila-tandeo').forEach(function (fila) {
            const coincideTexto = fila.dataset.busqueda.includes(texto);
            const coincideZona = zona === 'todas' || fila.dataset.zona === zona;
            const coincideEstado = estado === 'todos' || fila.dataset.estado === estado;
            const coincideDesde = desde === '' || fila.dataset.fecha >= desde;
            const coincideHasta = hasta === '' || fila.dataset.fecha <= hasta;
            const mostrar = coincideTexto && coincideZona && coincideEstado && coincideDesde && coincideHasta;

            fila.classList.toggle('d-none', !mostrar);
            if (mostrar) visibles++;
        });

        document.getElementById('sin-resultados').classList.toggle('d-none', visibles > 0);
    }

    document.querySelectorAll('#buscar-tandeo, #filtro-zona, #filtro-estado, #fecha-desde, #fecha-hasta').forEach(function (filtro) {
        filtro.addEventListener('input', filtrarTandeos);
        filtro.addEventListener('change', filtrarTandeos);
    });
</script>
@endsection






