<?php

namespace App\Http\Controllers;

// archivo del sistema: archivo con acciones para las pantallas del sistema

use App\Exports\CumplimientoCircuitoExport;
use App\Models\CircuitosModel;
use App\Models\Reprogramacion;
use App\Models\TandeoProgramado;
use App\Models\UsuarioModel;
use App\Models\ZonasModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportesController extends Controller
{
    // consulta y muestra los registros de la pantalla
    // Consulta los registros y prepara la pantalla de listado.
    // descripcion: consulta los registros y prepara la pantalla de listado.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: muestra la pantalla con los datos consultados
    public function index(Request $request)
    {
        $zonas = ZonasModel::where('activo', true)->orderBy('nombre')->get();
        $circuitos = CircuitosModel::with('zona')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $tandeadores = UsuarioModel::where('rol', 'tandeador')
            ->where('activo', true)
            ->orderBy('apellido_paterno')
            ->orderBy('nombre')
            ->get();

        $limpiarFiltros = $request->boolean('limpiar');
        $desde = $limpiarFiltros ? '' : $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $limpiarFiltros ? '' : $request->input('hasta', now()->toDateString());

        return view('reportes.index', [
            'zonas' => $zonas,
            'circuitos' => $circuitos,
            'tandeadores' => $tandeadores,
            'desde' => $desde,
            'hasta' => $hasta,
            'zonaSeleccionada' => $limpiarFiltros ? '' : $request->input('zona_id', ''),
            'circuitoSeleccionado' => $limpiarFiltros ? '' : $request->input('circuito_id', ''),
            'tandeadorSeleccionado' => $limpiarFiltros ? '' : $request->input('tandeador_id', ''),
        ]);
    }

    // prepara la vista previa del reporte seleccionado
    // descripcion: consulta los datos usando los filtros seleccionados y los prepara para mostrarlos
    // entradas: tipo de reporte y filtros de colonia, circuito y fechas
    // salida: muestra una vista previa con una tabla y, para cumplimiento, una grafica de pastel
    public function vistaPrevia(Request $request, string $tipo)
    {
        $tiposPermitidos = [
            'cumplimiento-zona',
            'reprogramaciones-motivo',
            'cumplimiento-circuito',
            'pendientes',
            'atrasos-colonia',
            'resumen-semanal-tandeador',
        ];
        abort_unless(in_array($tipo, $tiposPermitidos, true), 404);

        $filtros = $this->validarFiltros($request);
        $filas = [];
        $grafica = ['cumplidos' => 0, 'atrasados' => 0, 'no_cumplidos' => 0];

        $periodoReporte = null;
        $diasSemana = [];
        $tandeadorReporte = null;

        if ($tipo === 'atrasos-colonia') {
            $filas = $this->filasAtrasosPorColonia($filtros);
            $titulo = 'Atrasos por colonia';
        } elseif ($tipo === 'resumen-semanal-tandeador') {
            $request->validate([
                'tandeador_id' => ['required', 'integer', 'exists:usuarios,id'],
            ]);
            $filtros['tandeador_id'] = (int) $request->input('tandeador_id');
            $tandeadorReporte = UsuarioModel::where('rol', 'tandeador')->findOrFail($filtros['tandeador_id']);
            $diasSemana = $this->diasSemanaActual();
            $filas = $this->matrizResumenSemanalTandeador(
                $this->filasResumenSemanalTandeadores($filtros)
            );
            $inicioSemana = now()->startOfWeek();
            $finSemana = now()->endOfWeek();
            $periodoReporte = $inicioSemana->locale('es')->translatedFormat('d/m/Y')
                .' a '.$finSemana->locale('es')->translatedFormat('d/m/Y');
            $titulo = 'Resumen semanal por tandeador';
        } elseif ($tipo === 'cumplimiento-zona') {
            $registros = $this->tandeosFiltrados($filtros)->get()
                ->map(function ($tandeo) {
                    return [
                        'zona' => $tandeo->circuito->zona->nombre,
                        'resultado' => $tandeo->cumplimientos->first()?->resultado,
                    ];
                })
                ->filter(fn ($registro) => $registro['resultado'] !== null);

            $filas = $registros->groupBy('zona')
                ->map(function ($registrosZona, $nombreZona) {
                    $totalZona = $registrosZona->count();
                    $cumplidos = $registrosZona->where('resultado', 'cumplido')->count();
                    $atrasados = $registrosZona->where('resultado', 'atraso')->count();
                    $noCumplidos = $registrosZona->where('resultado', 'no_cumplido')->count();

                    return [
                        'zona' => $nombreZona,
                        'cumplidos' => $cumplidos,
                        'atrasados' => $atrasados,
                        'no_cumplidos' => $noCumplidos,
                        'total' => $totalZona,
                        'efectividad' => round(($cumplidos / $totalZona) * 100, 1),
                    ];
                })
                ->values()
                ->all();

            $grafica = [
                'cumplidos' => array_sum(array_column($filas, 'cumplidos')),
                'atrasados' => array_sum(array_column($filas, 'atrasados')),
                'no_cumplidos' => array_sum(array_column($filas, 'no_cumplidos')),
            ];
            $titulo = 'Tandeos cumplidos y atrasados por colonia';
        } elseif ($tipo === 'reprogramaciones-motivo') {
            $registros = Reprogramacion::with('tandeo.circuito.zona')
                ->when($filtros['circuito_id'] ?? null, function ($consulta, $circuitoId) {
                    $consulta->whereHas('tandeo', fn ($tandeos) => $tandeos->where('circuito_id', $circuitoId));
                })
                ->when($filtros['zona_id'] ?? null, function ($consulta, $zonaId) {
                    $consulta->whereHas('tandeo.circuito', fn ($circuitos) => $circuitos->where('zona_id', $zonaId));
                })
                ->when($filtros['desde'] ?? null, fn ($consulta, $desde) => $consulta->whereDate('created_at', '>=', $desde))
                ->when($filtros['hasta'] ?? null, fn ($consulta, $hasta) => $consulta->whereDate('created_at', '<=', $hasta))
                ->get();

            $filas = $registros->groupBy('tipo_origen')
                ->map(fn ($grupo, $motivo) => [
                    'motivo' => $this->nombreMotivo($motivo),
                    'cantidad' => $grupo->count(),
                ])
                ->sortByDesc('cantidad')
                ->values()
                ->all();
            $titulo = 'Reprogramaciones por motivo';
        } elseif ($tipo === 'cumplimiento-circuito') {
            $filas = $this->tandeosFiltrados($filtros)->get()
                ->groupBy('circuito_id')
                ->map(function ($grupo) {
                    $primerTandeo = $grupo->first();
                    $resultados = $grupo->map(fn ($tandeo) => $tandeo->cumplimientos->first()?->resultado);

                    return [
                        'circuito' => $primerTandeo->circuito->nombre,
                        'zona' => $primerTandeo->circuito->zona->nombre,
                        'calle' => $primerTandeo->circuito->descripcion,
                        'programados' => $grupo->count(),
                        'ejecutados' => $resultados->filter(fn ($resultado) => in_array($resultado, ['cumplido', 'atraso'], true))->count(),
                        'desfasados' => $resultados->filter(fn ($resultado) => $resultado === 'atraso')->count(),
                    ];
                })
                ->values()
                ->all();
            $titulo = 'Cumplimiento detallado por circuito';
        } else {
            $filas = $this->tandeosFiltrados($filtros)->get()
                ->filter(fn ($tandeo) => $tandeo->fin->isPast() && $tandeo->cumplimientos->isEmpty())
                ->map(fn ($tandeo) => [
                    'circuito' => $tandeo->circuito->nombre,
                    'zona' => $tandeo->circuito->zona->nombre,
                    'calle' => $tandeo->circuito->descripcion,
                    'fecha' => $tandeo->fecha->format('d/m/Y'),
                    'horario' => $tandeo->hora_inicio_programada.' - '.$tandeo->hora_fin_programada,
                ])
                ->values()
                ->all();
            $titulo = 'Resumen de tandeos pendientes de registrar';
        }

        $periodo = $periodoReporte ?? (($filtros['desde'] ?? 'Sin fecha inicial').' a '.($filtros['hasta'] ?? 'Sin fecha final'));
        $zona = isset($filtros['zona_id']) ? ZonasModel::find($filtros['zona_id'])?->nombre : 'Todas las colonias';
        $circuito = isset($filtros['circuito_id']) ? CircuitosModel::find($filtros['circuito_id'])?->nombre : 'Todos los circuitos';

        return view('reportes.index', [
            'zonas' => ZonasModel::where('activo', true)->orderBy('nombre')->get(),
            'circuitos' => CircuitosModel::with('zona')->where('activo', true)->orderBy('nombre')->get(),
            'tandeadores' => UsuarioModel::where('rol', 'tandeador')->where('activo', true)->orderBy('apellido_paterno')->orderBy('nombre')->get(),
            'desde' => $filtros['desde'] ?? '',
            'hasta' => $filtros['hasta'] ?? '',
            'zonaSeleccionada' => $filtros['zona_id'] ?? '',
            'circuitoSeleccionado' => $filtros['circuito_id'] ?? '',
            'tandeadorSeleccionado' => $filtros['tandeador_id'] ?? '',
            'tandeadorReporte' => $tandeadorReporte,
            'diasSemana' => $diasSemana,
            'tipoReporte' => $tipo,
            'tituloReporte' => $titulo,
            'filasPreview' => $filas,
            'totalPreview' => count($filas),
            'grafica' => $grafica,
            'periodo' => $periodo,
            'zonaReporte' => $zona,
            'circuitoReporte' => $circuito,
        ]);
    }

    // genera el reporte en pdf con los filtros seleccionados
    // Prepara y descarga el reporte solicitado en formato PDF.
    // descripcion: prepara y descarga el reporte solicitado en formato PDF.
    // entradas: solicitud con los datos enviados por el usuario; dato necesario para realizar el proceso
    // salida: descarga el reporte PDF con los filtros seleccionados
    public function generarPdf(Request $request, string $tipo)
    {
        $tiposPermitidos = ['cumplimiento-zona', 'reprogramaciones-motivo', 'pendientes', 'atrasos-colonia', 'resumen-semanal-tandeador'];
        abort_unless(in_array($tipo, $tiposPermitidos, true), 404);

        $filtros = $this->validarFiltros($request);
        $filas = [];
        $total = 0;
        $periodoReporte = null;
        $diasSemana = [];
        $tandeadorReporte = null;

        if ($tipo === 'atrasos-colonia') {
            $filas = $this->filasAtrasosPorColonia($filtros);
            $total = count($filas);
            $titulo = 'Atrasos por colonia';
        } elseif ($tipo === 'resumen-semanal-tandeador') {
            $request->validate([
                'tandeador_id' => ['required', 'integer', 'exists:usuarios,id'],
            ]);
            $filtros['tandeador_id'] = (int) $request->input('tandeador_id');
            $tandeadorReporte = UsuarioModel::where('rol', 'tandeador')->findOrFail($filtros['tandeador_id']);
            $diasSemana = $this->diasSemanaActual();
            $filas = $this->matrizResumenSemanalTandeador(
                $this->filasResumenSemanalTandeadores($filtros)
            );
            $total = count($filas);
            $inicioSemana = now()->startOfWeek();
            $finSemana = now()->endOfWeek();
            $periodoReporte = $inicioSemana->locale('es')->translatedFormat('d/m/Y')
                .' a '.$finSemana->locale('es')->translatedFormat('d/m/Y');
            $titulo = 'Resumen semanal por tandeador';
        } elseif ($tipo === 'cumplimiento-zona') {
            $tandeos = $this->tandeosFiltrados($filtros)->get();

            $registros = $tandeos
                // Prepara cada registro para guardarlo o mostrarlo.
                // descripcion: prepara cada registro para guardarlo o mostrarlo.
                // entradas: registro que se está transformando
                // salida: devuelve el registro con sus datos preparados
                ->map(function ($tandeo) {
                    return [
                        'zona' => $tandeo->circuito->zona->nombre,
                        'resultado' => $tandeo->cumplimientos->first()?->resultado,
                    ];
                })
                // Conserva solo los registros que cumplen la condición indicada.
                // descripcion: conserva solo los registros que cumplen la condición indicada.
                // entradas: registro que se está revisando
                // salida: indica si el registro se conserva o se descarta
                ->filter(fn ($registro) => $registro['resultado'] !== null);

            $filas = $registros
                ->groupBy('zona')
                // Prepara cada registro para guardarlo o mostrarlo.
                // descripcion: prepara cada registro para guardarlo o mostrarlo.
                // entradas: registro que se está transformando
                // salida: devuelve el registro con sus datos preparados
                ->map(function ($registrosZona, $nombreZona) {
                    $totalZona = $registrosZona->count();
                    $cumplidos = $registrosZona->where('resultado', 'cumplido')->count();
                    $atrasados = $registrosZona->where('resultado', 'atraso')->count();
                    $noCumplidos = $registrosZona->where('resultado', 'no_cumplido')->count();

                    return [
                        'zona' => $nombreZona,
                        'cumplidos' => $cumplidos,
                        'atrasados' => $atrasados,
                        'no_cumplidos' => $noCumplidos,
                        'total' => $totalZona,
                        'efectividad' => round(($cumplidos / $totalZona) * 100, 1),
                        'porcentaje_cumplidos' => round(($cumplidos / $totalZona) * 100, 1),
                        'porcentaje_atrasados' => round(($atrasados / $totalZona) * 100, 1),
                        'porcentaje_no_cumplidos' => round(($noCumplidos / $totalZona) * 100, 1),
                    ];
                })
                ->values()
                ->all();

            $total = count($registros);
            $titulo = 'Tandeos cumplidos y atrasados por colonia';
        } elseif ($tipo === 'reprogramaciones-motivo') {
            $consulta = Reprogramacion::with('tandeo.circuito.zona')
                // Aplica el filtro solo cuando se recibió el dato correspondiente.
                // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
                // entradas: consulta y valor del filtro elegido
                // salida: añade el filtro a la consulta cuando se necesita
                ->when($filtros['circuito_id'] ?? null, function ($consulta, $circuitoId) {
                    // Filtra los registros según la relación que deben cumplir.
                    // descripcion: filtra los registros según la relación que deben cumplir.
                    // entradas: consulta y registros relacionados
                    // salida: añade la condición correspondiente a la consulta
                    $consulta->whereHas('tandeo', fn ($tandeos) => $tandeos->where('circuito_id', $circuitoId));
                })
                // Aplica el filtro solo cuando se recibió el dato correspondiente.
                // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
                // entradas: consulta y valor del filtro elegido
                // salida: añade el filtro a la consulta cuando se necesita
                ->when($filtros['zona_id'] ?? null, function ($consulta, $zonaId) {
                    // Filtra los registros según la relación que deben cumplir.
                    // descripcion: filtra los registros según la relación que deben cumplir.
                    // entradas: consulta y registros relacionados
                    // salida: añade la condición correspondiente a la consulta
                    $consulta->whereHas('tandeo.circuito', fn ($circuitos) => $circuitos->where('zona_id', $zonaId));
                })
                // Aplica el filtro solo cuando se recibió el dato correspondiente.
                // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
                // entradas: consulta y valor del filtro elegido
                // salida: añade el filtro a la consulta cuando se necesita
                ->when($filtros['desde'] ?? null, fn ($consulta, $desde) => $consulta->whereDate('created_at', '>=', $desde))
                // Aplica el filtro solo cuando se recibió el dato correspondiente.
                // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
                // entradas: consulta y valor del filtro elegido
                // salida: añade el filtro a la consulta cuando se necesita
                ->when($filtros['hasta'] ?? null, fn ($consulta, $hasta) => $consulta->whereDate('created_at', '<=', $hasta))
                ->get();

            $filas = $consulta
                ->groupBy('tipo_origen')
                // Prepara cada registro para guardarlo o mostrarlo.
                // descripcion: prepara cada registro para guardarlo o mostrarlo.
                // entradas: registro que se está transformando
                // salida: devuelve el registro con sus datos preparados
                ->map(fn ($registros, $motivo) => [
                    'motivo' => $this->nombreMotivo($motivo),
                    'cantidad' => $registros->count(),
                ])
                ->sortByDesc('cantidad')
                ->values()
                ->all();

            $total = $consulta->count();
            $titulo = 'Reprogramaciones por motivo';
        } else {
            $tandeos = $this->tandeosFiltrados($filtros)->get();

            $filas = $tandeos
                // Conserva solo los registros que cumplen la condición indicada.
                // descripcion: conserva solo los registros que cumplen la condición indicada.
                // entradas: registro que se está revisando
                // salida: indica si el registro se conserva o se descarta
                ->filter(fn ($tandeo) => $tandeo->fin->isPast() && $tandeo->cumplimientos->isEmpty())
                // Prepara cada registro para guardarlo o mostrarlo.
                // descripcion: prepara cada registro para guardarlo o mostrarlo.
                // entradas: registro que se está transformando
                // salida: devuelve el registro con sus datos preparados
                ->map(fn ($tandeo) => [
                    'circuito' => $tandeo->circuito->nombre,
                    'zona' => $tandeo->circuito->zona->nombre,
                    'calle' => $tandeo->circuito->descripcion,
                    'fecha' => $tandeo->fecha->format('d/m/Y'),
                    'horario' => $tandeo->hora_inicio_programada.' - '.$tandeo->hora_fin_programada,
                ])
                ->values()
                ->all();

            $total = count($filas);
            $titulo = 'Resumen de tandeos pendientes de registrar';
        }

        $periodo = $periodoReporte ?? (($filtros['desde'] ?? 'Sin fecha inicial')
            .' a '
            .($filtros['hasta'] ?? 'Sin fecha final'));
        $zona = isset($filtros['zona_id'])
            ? ZonasModel::find($filtros['zona_id'])?->nombre
            : 'Todas las colonias';
        $circuito = isset($filtros['circuito_id'])
            ? CircuitosModel::find($filtros['circuito_id'])?->nombre
            : 'Todos los circuitos';

        return Pdf::loadView('reportes.pdf', compact(
            'tipo',
            'titulo',
            'filas',
            'diasSemana',
            'tandeadorReporte',
            'total',
            'periodo',
            'zona',
            'circuito'
        ))->setPaper('a4', 'landscape')
            ->download('reporte-'.$tipo.'-'.now()->format('Ymd-His').'.pdf');
    }

    // prepara y descarga el reporte de cumplimiento por circuito
    // Prepara y descarga el cumplimiento por circuito en Excel.
    // descripcion: prepara y descarga el cumplimiento por circuito en Excel.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: descarga el archivo de Excel con los resultados consultados
    public function cumplimientoCircuitoExcel(Request $request)
    {
        $filtros = $this->validarFiltros($request);
        $tandeos = $this->tandeosFiltrados($filtros)->get();

        $filas = $tandeos
            ->groupBy('circuito_id')
            // Prepara cada registro para guardarlo o mostrarlo.
            // descripcion: prepara cada registro para guardarlo o mostrarlo.
            // entradas: registro que se está transformando
            // salida: devuelve el registro con sus datos preparados
            ->map(function ($tandeosCircuito) {
                $primerTandeo = $tandeosCircuito->first();
                $resultados = $tandeosCircuito->map(
                    // Realiza una parte del proceso que la llamó.
                    // descripcion: realiza una parte del proceso que la llamó.
                    // entradas: registro que se va a procesar
                    // salida: devuelve o modifica los datos que necesita el proceso principal
                    fn ($tandeo) => $tandeo->cumplimientos->first()?->resultado
                );

                return [
                    $primerTandeo->circuito->nombre,
                    $primerTandeo->circuito->zona->nombre,
                    $primerTandeo->circuito->descripcion,
                    $tandeosCircuito->count(),
                    // Conserva solo los registros que cumplen la condición indicada.
                    // descripcion: conserva solo los registros que cumplen la condición indicada.
                    // entradas: registro que se está revisando
                    // salida: indica si el registro se conserva o se descarta
                    $resultados->filter(fn ($resultado) => in_array($resultado, ['cumplido', 'atraso'], true))->count(),
                    // Conserva solo los registros que cumplen la condición indicada.
                    // descripcion: conserva solo los registros que cumplen la condición indicada.
                    // entradas: registro que se está revisando
                    // salida: indica si el registro se conserva o se descarta
                    $resultados->filter(fn ($resultado) => $resultado === 'atraso')->count(),
                ];
            })
            ->values()
            ->all();

        return Excel::download(
            new CumplimientoCircuitoExport($filas),
            'cumplimiento-por-circuito.xlsx'
        );
    }

    // revisa los filtros recibidos para generar el reporte
    // Valida los filtros antes de consultar o generar el reporte.
    // descripcion: valida los filtros antes de consultar o generar el reporte.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: devuelve los filtros válidos o los errores encontrados
    private function validarFiltros(Request $request): array
    {
        return $request->validate([
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'circuito_id' => ['nullable', 'integer', 'exists:circuitos,id'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'tandeador_id' => ['nullable', 'integer', 'exists:usuarios,id'],
        ]);
    }

    /**
     * Prepara los movimientos con días de diferencia entre la fecha anterior y la nueva.
     *
     * @param  array  $filtros  Filtros de colonia, circuito y fechas.
     * @return array Lista ordenada de tandeos atrasados y su motivo.
     */
    private function filasAtrasosPorColonia(array $filtros): array
    {
        return Reprogramacion::with('tandeo.circuito.zona')
            ->when($filtros['circuito_id'] ?? null, function ($consulta, $circuitoId) {
                $consulta->whereHas('tandeo', fn ($tandeos) => $tandeos->where('circuito_id', $circuitoId));
            })
            ->when($filtros['zona_id'] ?? null, function ($consulta, $zonaId) {
                $consulta->whereHas('tandeo.circuito', fn ($circuitos) => $circuitos->where('zona_id', $zonaId));
            })
            ->when($filtros['desde'] ?? null, fn ($consulta, $desde) => $consulta->whereDate('created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($consulta, $hasta) => $consulta->whereDate('created_at', '<=', $hasta))
            ->get()
            ->map(function ($movimiento) {
                $tandeo = $movimiento->tandeo;
                if (! $tandeo || ! $tandeo->circuito) {
                    return null;
                }

                $inicioAnterior = $movimiento->inicio_anterior;
                $inicioNuevo = $movimiento->inicio_nuevo;
                $diasAtraso = 0;

                if ($inicioNuevo->greaterThan($inicioAnterior)) {
                    $diasAtraso = (int) $inicioAnterior->copy()->startOfDay()
                        ->diffInDays($inicioNuevo->copy()->startOfDay());
                }

                return [
                    'colonia' => $tandeo->circuito->zona->nombre ?? 'Sin colonia',
                    'tandeo' => $tandeo->circuito->nombre.' · '.($tandeo->circuito->descripcion ?: 'Sin calles registradas'),
                    'dias' => $diasAtraso,
                    'motivo' => $this->motivoDelMovimiento($movimiento),
                ];
            })
            ->filter()
            ->sortByDesc('dias')
            ->values()
            ->all();
    }

    /**
     * Prepara los tandeos de esta semana agrupados por las personas asignadas.
     *
     * @param  array  $filtros  Filtros opcionales de colonia y circuito.
     * @return array Tandeos ordenados por tandeador, fecha y horario.
     */
    private function filasResumenSemanalTandeadores(array $filtros): array
    {
        $filtrosSemana = [
            'desde' => now()->startOfWeek()->toDateString(),
            'hasta' => now()->endOfWeek()->toDateString(),
            'zona_id' => $filtros['zona_id'] ?? null,
            'circuito_id' => $filtros['circuito_id'] ?? null,
        ];

        $tandeos = $this->tandeosFiltrados($filtrosSemana)
            ->with(['tandeadores', 'tandeador'])
            ->where(function ($consulta) use ($filtros) {
                $tandeadorId = $filtros['tandeador_id'];
                $consulta->whereHas('tandeadores', fn ($asignados) => $asignados->where('usuarios.id', $tandeadorId))
                    ->orWhere('tandeador_id', $tandeadorId);
            })
            ->get();
        $filas = [];

        foreach ($tandeos as $tandeo) {
            $asignados = $tandeo->tandeadores
                ->where('id', (int) $filtros['tandeador_id'])
                ->values();
            if ($asignados->isEmpty() && (int) $tandeo->tandeador_id === (int) $filtros['tandeador_id'] && $tandeo->tandeador) {
                $asignados = collect([$tandeo->tandeador]);
            }

            $horaInicio = Carbon::parse($tandeo->hora_inicio_programada);
            $horaFin = Carbon::parse($tandeo->hora_fin_programada);
            $horario = $horaInicio->format('H:i').' - '.$horaFin->format('H:i');
            if ($horaFin->lessThanOrEqualTo($horaInicio)) {
                $horario .= ' (+1 día)';
            }

            foreach ($asignados as $tandeador) {
                $filas[] = [
                    'tandeador_id' => $tandeador->id,
                    'tandeador' => trim($tandeador->nombre.' '.$tandeador->apellido_paterno.' '.$tandeador->apellido_materno),
                    'circuito_id' => $tandeo->circuito_id,
                    'fecha' => $tandeo->fecha->locale('es')->translatedFormat('l d/m/Y'),
                    'fecha_iso' => $tandeo->fecha->format('Y-m-d'),
                    'orden_fecha' => $tandeo->fecha->format('Y-m-d'),
                    'colonia' => $tandeo->circuito->zona->nombre,
                    'circuito' => $tandeo->circuito->nombre,
                    'calle' => $tandeo->circuito->descripcion ?: 'Sin calles registradas',
                    'horario' => $horario,
                    'orden_hora' => $horaInicio->format('H:i'),
                ];
            }
        }

        usort($filas, fn ($a, $b) => [$a['tandeador'], $a['orden_fecha'], $a['orden_hora']] <=> [$b['tandeador'], $b['orden_fecha'], $b['orden_hora']]);

        $filas = array_map(function ($fila) {
            unset($fila['orden_fecha'], $fila['orden_hora']);

            return $fila;
        }, $filas);

        return $filas;
    }

    /**
     * Acomoda los horarios del tandeador en una fila por circuito y una columna por día.
     *
     * @param  array  $filas  Tandeos del tandeador seleccionado.
     * @return array Filas listas para la vista previa y el PDF.
     */
    private function matrizResumenSemanalTandeador(array $filas): array
    {
        $porCircuito = [];

        foreach ($filas as $fila) {
            $circuitoId = $fila['circuito_id'];

            if (! isset($porCircuito[$circuitoId])) {
                $porCircuito[$circuitoId] = [
                    'tandeador' => $fila['tandeador'],
                    'colonia' => $fila['colonia'],
                    'circuito' => $fila['circuito'],
                    'calle' => $fila['calle'],
                    'horarios' => [],
                ];
            }

            $porCircuito[$circuitoId]['horarios'][$fila['fecha_iso']][] = $fila['horario'];
        }

        return array_values($porCircuito);
    }

    /**
     * Prepara los encabezados de lunes a domingo de la semana actual.
     *
     * @return array Fechas que se muestran en las columnas del calendario semanal.
     */
    private function diasSemanaActual(): array
    {
        $inicioSemana = now()->startOfWeek();
        $dias = [];

        for ($numeroDia = 0; $numeroDia < 7; $numeroDia++) {
            $dia = $inicioSemana->copy()->addDays($numeroDia);
            $dias[] = [
                'fecha' => $dia->toDateString(),
                'titulo' => $dia->locale('es')->translatedFormat('D d/m'),
            ];
        }

        return $dias;
    }

    /**
     * Devuelve un motivo entendible para el movimiento del tandeo.
     *
     * @param  Reprogramacion  $movimiento  Movimiento guardado en la reprogramación.
     * @return string Motivo que se muestra en el reporte.
     */
    private function motivoDelMovimiento(Reprogramacion $movimiento): string
    {
        $motivo = $this->nombreMotivo($movimiento->tipo_origen);

        if ($movimiento->tipo_origen === 'manual'
            && $movimiento->observaciones
            && $movimiento->observaciones !== 'Recorrido por empalme') {
            $motivo = $movimiento->observaciones;
        }

        if ($movimiento->es_efecto_cadena) {
            $motivo .= ' (recorrido por empalme)';
        }

        return $motivo;
    }

    // consulta los tandeos que cumplen con los filtros
    // Consulta los tandeos que coinciden con los filtros elegidos.
    // descripcion: consulta los tandeos que coinciden con los filtros elegidos.
    // entradas: lista de datos que se deben procesar
    // salida: devuelve los tandeos que cumplen con los filtros
    private function tandeosFiltrados(array $filtros)
    {
        return TandeoProgramado::with([
            'circuito.zona',
            // Ordena los cumplimientos para consultar primero el registro más reciente.
            // descripcion: ordena los cumplimientos para consultar primero el registro más reciente.
            // entradas: cumplimientos relacionados con el tandeo
            // salida: devuelve los cumplimientos ordenados por fecha de captura
            'cumplimientos' => fn ($consulta) => $consulta
                ->orderByDesc('fecha_captura')
                ->orderByDesc('id'),
        ])
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when($filtros['zona_id'] ?? null, function ($consulta, $zonaId) {
                // Filtra los registros según la relación que deben cumplir.
                // descripcion: filtra los registros según la relación que deben cumplir.
                // entradas: consulta y registros relacionados
                // salida: añade la condición correspondiente a la consulta
                $consulta->whereHas('circuito', fn ($circuitos) => $circuitos->where('zona_id', $zonaId));
            })
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when($filtros['circuito_id'] ?? null, fn ($consulta, $circuitoId) => $consulta->where('circuito_id', $circuitoId))
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when($filtros['desde'] ?? null, fn ($consulta, $desde) => $consulta->whereDate('fecha', '>=', $desde))
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when($filtros['hasta'] ?? null, fn ($consulta, $hasta) => $consulta->whereDate('fecha', '<=', $hasta))
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada');
    }

    // muestra el nombre entendible del motivo registrado
    // Prepara el nombre legible del motivo guardado.
    // descripcion: prepara el nombre legible del motivo guardado.
    // entradas: dato necesario para realizar el proceso
    // salida: devuelve el texto que se muestra para el motivo
    private function nombreMotivo(string $motivo): string
    {
        return match ($motivo) {
            'apagon_general' => 'Apagón general',
            'fuga_laguna' => 'Fuga en laguna',
            'fuga_pozo' => 'Fuga en pozo',
            'manual' => 'Reprogramación manual',
            default => str_replace('_', ' ', ucfirst($motivo)),
        };
    }
}
