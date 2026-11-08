{{-- vista de tandeosasociados en el modulo circuitos --}}
{{-- archivo del sistema: archivo de codigo del sistema --}}
@extends('layouts.app')

@section('titulo', 'Circuito con tandeos - SIGETA')

@section('estilos')
{{-- estilos visuales de la pantalla --}}
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
<h1>¡Cuidado!</h1>

    <section class="panel">
        <p>El circuito <strong>{{ $circuito->nombre }}</strong> tiene tandeos o repeticiones asociados. Si lo desactivas, esos datos se conservarán y no se podrán programar nuevos tandeos. Si lo eliminas definitivamente, se borrarán el circuito y los tandeos, cumplimientos, reprogramaciones y repeticiones relacionados. Esta acción no se puede deshacer.</p>

        <h3>Tandeos asociados</h3>
        @if ($tandeosAsociados->isEmpty())
            <p>No hay tandeos generados para este circuito.</p>
        @else
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Lugar / tandeos</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tandeosAsociados as $tandeo)
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
                        <tr>
                            <td>{{ $circuito->descripcion ?: 'Sin calles registradas' }}</td>
                            <td>{{ \Carbon\Carbon::parse($tandeo->fecha)->locale('es')->translatedFormat('l d/M/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($tandeo->hora_inicio_programada)->format('G:i') }} - {{ \Carbon\Carbon::parse($tandeo->hora_fin_programada)->format('G:i') }}</td>
                            <td><span class="estado-badge estado-{{ $estado }}">{{ $nombresEstados[$estado] ?? ucfirst(str_replace('_', ' ', $estado)) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($repeticionesAsociadas->isNotEmpty())
            <h3 class="mt-4">Repeticiones configuradas</h3>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha inicial</th>
                            <th>Se repite cada</th>
                            <th>Hora de inicio</th>
                            <th>Duración</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($repeticionesAsociadas as $repeticion)
                            <tr>
                                <td>{{ $repeticion->fecha_ancla->locale('es')->translatedFormat('l d/M/Y') }}</td>
                                <td>{{ $repeticion->cada_dias }} días</td>
                                <td>{{ \Carbon\Carbon::parse($repeticion->hora_inicio)->format('G:i') }}</td>
                                <td>{{ $repeticion->duracion_minutos / 60 }} horas</td>
                                <td>{{ $repeticion->activa ? 'Activa' : 'Pausada' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="acciones">
            <a href="{{ route('circuitos.index') }}" class="boton boton-secundario" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); return false; }">Volver a circuitos</a>
            <div class="d-flex flex-wrap gap-2">
                <form action="{{ route('desactivarCircuito', $circuito->id) }}" method="POST"
                      data-confirmacion-titulo="Desactivar circuito" data-confirmacion-mensaje="Se conservarán los tandeos y las repeticiones. El circuito ya no podrá usarse para programar nuevos.">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn btn-outline-secondary">Desactivar circuito</button>
                </form>
                <form action="{{ route('deleteCircuito', $circuito->id) }}" method="POST"
                      data-confirmacion-titulo="Eliminar circuito definitivamente"
                      data-confirmacion-mensaje="Se eliminarán el circuito, sus tandeos, cumplimientos, reprogramaciones y repeticiones. Las notificaciones se conservarán sin el tandeo relacionado. Esta acción no se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="eliminar_definitivamente" value="1">
                    <input type="hidden" name="confirmar" value="0">
                    <button type="submit" class="btn btn-danger"><span class="icono-boton" aria-hidden="true"><svg><use href="#icono-eliminar"/></svg></span>Eliminar definitivamente</button>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection

