{{-- vista de pdf en el modulo reportes --}}
{{-- archivo del sistema: archivo de codigo del sistema --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    {{-- estilos visuales de la pantalla --}}
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #243447; font-size: 11px; }
        h1 { text-align: center; font-size: 20px; margin-bottom: 6px; }
        .resumen { text-align: center; color: #555; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #bcc7d1; padding: 7px; vertical-align: middle; }
        th { background: #eaf1f7; text-align: center; }
        td.centro { text-align: center; }
        .barra { width: 100%; height: 14px; background: #eeeeee; white-space: nowrap; }
        .verde { display: inline-block; height: 14px; background: #3b8d63; }
        .naranja { display: inline-block; height: 14px; background: #e59b36; }
        .rojo { display: inline-block; height: 14px; background: #c94c4c; }
        .leyenda { margin-top: 8px; text-align: center; }
        .punto { display: inline-block; width: 9px; height: 9px; margin-left: 14px; }
        .vacio { text-align: center; padding: 16px; color: #666; }
        .firma { width: 44%; display: inline-block; margin: 38px 2% 18px; text-align: center; vertical-align: top; }
        .firma-linea { border-top: 1px solid #333; padding-top: 7px; }
        .nota-horarios { margin-top: 18px; text-align: center; font-size: 10px; }
        .tandeador { margin-top: 22px; page-break-inside: avoid; }
        .tabla-semanal { table-layout: fixed; font-size: 8px; }
        .tabla-semanal th, .tabla-semanal td { padding: 4px 3px; text-align: center; }
        .tabla-semanal .descripcion { text-align: left; }
        .semana-titulo { text-align: center; font-size: 14px; margin: 8px 0 14px; }
    </style>
</head>
<body>
    <h1>{{ $titulo }}</h1>
    <div class="resumen">
        Periodo: {{ $periodo }} |
        Colonia: {{ $zona }} |
        Circuito: {{ $circuito }} |
        Registros: {{ $total }}
    </div>

    @if ($tipo === 'cumplimiento-zona')
        <table>
            <thead>
                <tr>
                    <th>Colonia</th>
                    <th>Cumplidos</th>
                    <th>Con atraso</th>
                    <th>No cumplidos</th>
                    <th>Total registrados</th>
                    <th>Efectividad</th>
                    <th>Comparación</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila['zona'] }}</td>
                        <td class="centro">{{ $fila['cumplidos'] }}</td>
                        <td class="centro">{{ $fila['atrasados'] }}</td>
                        <td class="centro">{{ $fila['no_cumplidos'] }}</td>
                        <td class="centro">{{ $fila['total'] }}</td>
                        <td class="centro">{{ number_format($fila['efectividad'], 1) }}%</td>
                        <td>
                            <div class="barra">
                                <span class="verde" style="width: {{ $fila['porcentaje_cumplidos'] }}%;"></span><span class="naranja" style="width: {{ $fila['porcentaje_atrasados'] }}%;"></span><span class="rojo" style="width: {{ $fila['porcentaje_no_cumplidos'] }}%;"></span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="vacio" colspan="7">No hay cumplimientos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="leyenda">
            <span class="punto" style="background:#3b8d63;"></span> Cumplido
            <span class="punto" style="background:#e59b36;"></span> Con atraso
            <span class="punto" style="background:#c94c4c;"></span> No cumplido
        </div>
        <p>La efectividad se calcula como tandeos cumplidos entre el total de cumplimientos registrados.</p>
    @elseif ($tipo === 'atrasos-colonia')
        <table>
            <thead><tr><th>Colonia</th><th>Tandeo / lugar</th><th>Días de atraso</th><th>Motivo</th></tr></thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr><td>{{ $fila['colonia'] }}</td><td>{{ $fila['tandeo'] }}</td><td class="centro">{{ $fila['dias'] }}</td><td>{{ $fila['motivo'] }}</td></tr>
                @empty
                    <tr><td class="vacio" colspan="4">No hay atrasos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($tipo === 'resumen-semanal-tandeador')
        <h2>Tandeador: {{ $tandeadorReporte->nombre }} {{ $tandeadorReporte->apellido_paterno }} {{ $tandeadorReporte->apellido_materno }}</h2>
        <div class="semana-titulo">Semana del {{ $periodo }}</div>
        <table class="tabla-semanal">
            <thead>
                <tr><th>Colonia</th><th>Descripción</th>@foreach ($diasSemana as $dia)<th>{{ $dia['titulo'] }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila['colonia'] }}</td><td class="descripcion">{{ $fila['calle'] }}</td>
                        @foreach ($diasSemana as $dia)
                            <td>@foreach (($fila['horarios'][$dia['fecha']] ?? []) as $horario)<div>{{ $horario }}</div>@endforeach</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td class="vacio" colspan="{{ 2 + count($diasSemana) }}">Este tandeador no tiene tandeos asignados esta semana.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="firma"><div class="firma-linea">Despachador</div></div>
        <div class="firma"><div class="firma-linea">Trabajador</div></div>
        <p class="nota-horarios">Los cambios en los horarios dependerán de circunstancias ajenas (fallas de luz, fugas, apagones, etc.).</p>
    @elseif ($tipo === 'reprogramaciones-motivo')
        <table>
            <thead><tr><th>Motivo</th><th>Reprogramaciones</th></tr></thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr><td>{{ $fila['motivo'] }}</td><td class="centro">{{ $fila['cantidad'] }}</td></tr>
                @empty
                    <tr><td class="vacio" colspan="2">No hay reprogramaciones para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th>Circuito</th>
                    <th>Colonia</th>
                    <th>Lugar/tandeo</th>
                    <th>Fecha</th>
                    <th>Horario</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila['circuito'] }}</td>
                        <td>{{ $fila['zona'] }}</td>
                        <td>{{ $fila['calle'] }}</td>
                        <td class="centro">{{ $fila['fecha'] }}</td>
                        <td class="centro">{{ $fila['horario'] }}</td>
                    </tr>
                @empty
                    <tr><td class="vacio" colspan="5">No hay tandeos pendientes para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
