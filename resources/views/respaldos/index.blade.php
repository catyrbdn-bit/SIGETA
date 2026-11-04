@extends('layouts.app')

@section('titulo', 'Respaldos de base de datos - SIGETA')

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h2>Respaldos de base de datos</h2>
                <p class="text-muted mb-0">Crea copias y restaura una copia anterior si es necesario</p>
            </div>
            <form action="{{ route('respaldos.store') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">Generar respaldo</button>
            </form>
        </div>

        @if (session('success')) <div class="alert alert-success">
            {{ session('success') }}
        </div> 
        @endif

        @if (session('error')) <div class="alert alert-danger">
            {{ session('error') }}
        </div> 
        @endif

        @if ($errors->any()) <div class="alert alert-warning">
            {{ $errors->first() }}
        </div> 
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">Fecha y hora</th>
                        <th class="text-center">Archivo</th>
                        <th class="text-center">Tamaño</th>
                        <th class="text-center">Creado por</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($respaldos as $respaldo)
                        <tr>
                            <td class="text-center">{{ $respaldo->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $respaldo->nombre_archivo }}</td>
                            <td class="text-center">{{ $respaldo->tamano_bytes ? number_format($respaldo->tamano_bytes / 1048576, 2) . ' MB' : '—' }}</td>
                            <td class="text-center">{{ $respaldo->usuario->usuario ?? 'Usuario no disponible' }}</td>
                            <td class="text-center">{{ ucfirst($respaldo->estado) }}</td>
                            <td class="text-center">
                                <form action="{{ route('respaldos.restore', $respaldo) }}" method="POST"
                                    data-confirmacion-titulo="Confirmar restauración"
                                    data-confirmacion-mensaje="Se reemplazará la información actual por la de este respaldo. Antes se guardará automáticamente una copia de la base actual. ¿Deseas continuar?">
                                    @csrf
                                    <input type="hidden" name="confirmar" value="0">
                                    <button type="submit" class="btn btn-outline-primary">Restaurar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">Todavía no hay respaldos disponibles.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Volver</a>
    </div>
@endsection
