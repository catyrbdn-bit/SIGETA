@extends('layouts.app')

@section('titulo', 'Circuito con tandeos - SIGETA')

@section('estilos')
<style>

        * { box-sizing: border-box; }
.contenido-vista {
            max-width: 900px;
            margin: 32px auto;
            padding: 0 20px;
            font-family: Arial, sans-serif;
            color: #243447;
            background: #f5f7fa;
        }

        .panel {
            margin-top: 18px;
            padding: 20px;
            border: 1px solid #dce3ea;
            border-radius: 8px;
            background: #fff;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #e1e7ed; text-align: left; }
        th { background: #eef3f7; }

        .acciones {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 20px;
        }

        .boton {
            display: inline-block;
            padding: 10px 16px;
            border: 0;
            border-radius: 6px;
            color: #fff;
            background: #1f4e79;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .boton-secundario { color: #243447; background: #e1e7ed; }
        .boton-peligro { background: #a83232; }

        @media (max-width: 600px) {
            .tabla-contenedor { overflow-x: auto; }
            table { min-width: 500px; }
        }
    
</style>
@endsection

@section('content')
<div class="contenido-vista">
<h1>SIGETA</h1>
    <h2>No se puede eliminar este circuito</h2>

    <section class="panel">
        <p>El circuito <strong>{{ $circuito->nombre }}</strong> tiene tandeos asociados y no puede eliminarse de forma definitiva.</p>

        <h3>Tandeos asociados</h3>
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tandeosAsociados as $tandeo)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($tandeo->fecha)->locale('es')->translatedFormat('l d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($tandeo->hora_inicio_programada)->format('G:i') }}-{{ \Carbon\Carbon::parse($tandeo->hora_fin_programada)->format('G:i') }}</td>
                            <td>{{ $tandeo->estado }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="acciones">
            <a href="{{ route('circuitos.index') }}" class="boton boton-secundario" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); return false; }">Volver a circuitos</a>

            <form action="{{ route('desactivarCircuito', $circuito->id) }}" method="POST"
                  onsubmit="return confirm('¿Desactivar este circuito?');">
                @csrf
                @method('PUT')
                <button type="submit" class="boton boton-peligro">Desactivar circuito</button>
            </form>
        </div>
    </section>
</div>
@endsection
