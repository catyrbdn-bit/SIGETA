@extends('layouts.app')

@section('titulo', 'Panel principal - SIGETA')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3">Panel principal</h1>
        <p class="mb-1">Bienvenido, {{ Auth::user()->nombre }}</p>
        <p class="mb-1">Usuario: {{ Auth::user()->usuario }}</p>
        <p class="mb-0">Rol: {{ Auth::user()->rol }}</p>
    </div>

    <div style="min-width: 220px;">
        <label for="filtro-estado" class="form-label">Filtrar por estado</label>
        <select id="filtro-estado" class="form-select">
            <option value="todos">
                Todos los estados
            </option>
            <option value="programado">
                Programado
            </option>
            <option value="en_curso">
                En curso
            </option>
            <option value="pendiente">Pendiente de registrar</option>
            <option value="cumplido">
                Cumplido
            </option>
            <option value="atraso">
                Con atraso
            </option>
            <option value="no_cumplido">
                No cumplido
            </option>
        </select>
    </div>
</div>

<h2 class="h5 mb-3">Tandeos de hoy</h2>
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle" id="tabla-tandeos">
        <thead class="table-light">
            <tr>
                <th>Circuito / Colonia</th>
                <th>Horario</th>
                <th>Estado</th>
                <th>Punto de abastecimiento</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tandeosHoy as $tandeo)
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
                <tr class="fila-tandeo" data-estado="{{ $estado }}">
                    <td>
                        <strong>{{ $tandeo->circuito->nombre }}</strong><br>
                        <span class="text-muted">{{ $tandeo->circuito->zona->nombre }}</span>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($tandeo->hora_inicio_programada)->format('G:i') }}-{{ \Carbon\Carbon::parse($tandeo->hora_fin_programada)->format('G:i') }}</td>
                    <td>{{ $nombresEstados[$estado] ?? ucfirst(str_replace('_', ' ', $estado)) }}</td>
                    <td>{{ $tandeo->circuito->puntoAbastecimiento->nombre ?? 'Sin punto fijo' }}</td>
                    <td><a href="{{ route('cumplimiento.index', ['tandeo_id' => $tandeo->id]) }}" class="btn btn-sm btn-outline-primary">Ir a cumplimiento</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No hay tandeos programados para hoy.</td></tr>
            @endforelse
            <tr id="sin-tandeos-filtrados" class="d-none"><td colspan="5" class="text-center text-muted">No hay tandeos con ese estado.</td></tr>
        </tbody>
    </table>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
    <a href="{{ route('cumplimiento.index') }}" class="btn btn-outline-primary">
        Gestión de cumplimiento</a>
    <a href="{{ route('zonas.index') }}" class="btn btn-outline-secondary">
        Colonias</a>
    <a href="{{ route('circuitos.index') }}" class="btn btn-outline-secondary">
        Circuitos</a>
    <a href="{{ route('tandeos.index') }}" class="btn btn-outline-secondary">
        Tandeos programados</a>
</div>

<form action="{{ route('cerrarSesion') }}" method="POST" class="d-flex justify-content-end mt-3">
    @csrf
    <button type="submit" class="btn btn-link px-0">Cerrar sesión</button>
</form>

<script>
    document.getElementById('filtro-estado').addEventListener('change', function () {
        const estadoElegido = this.value;
        let cantidadVisible = 0;

        document.querySelectorAll('.fila-tandeo').forEach(function (fila) {
            const mostrar = estadoElegido === 'todos' || fila.dataset.estado === estadoElegido;
            fila.classList.toggle('d-none', !mostrar);
            if (mostrar) cantidadVisible++;
        });

        document.getElementById('sin-tandeos-filtrados').classList.toggle('d-none', cantidadVisible > 0);
    });
</script>
@endsection









