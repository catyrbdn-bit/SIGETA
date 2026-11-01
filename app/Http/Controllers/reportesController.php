<?php

namespace App\Http\Controllers;
// archivo del sistema: archivo con acciones para las pantallas del sistema

use App\Exports\CumplimientoCircuitoExport;
use App\Models\circuitosModel;
use App\Models\Reprogramacion;
use App\Models\tandeoProgramado;
use App\Models\zonasModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class reportesController extends controller
{
    // consulta y muestra los registros de la pantalla
    // Consulta los registros y prepara la pantalla de listado.
    // descripcion: consulta los registros y prepara la pantalla de listado.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: muestra la pantalla con los datos consultados
    public function index(Request $request)
    {
        $zonas = zonasModel::where('activo', true)->orderBy('nombre')->get();
        $circuitos = circuitosModel::with('zona')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());

        return view('reportes.index', [
            'zonas' => $zonas,
            'circuitos' => $circuitos,
            'desde' => $desde,
            'hasta' => $hasta,
            'zonaSeleccionada' => $request->input('zona_id', ''),
            'circuitoSeleccionado' => $request->input('circuito_id', ''),
        ]);
    }

    // genera el reporte en pdf con los filtros seleccionados
    // Prepara y descarga el reporte solicitado en formato PDF.
    // descripcion: prepara y descarga el reporte solicitado en formato PDF.
    // entradas: solicitud con los datos enviados por el usuario; dato necesario para realizar el proceso
    // salida: descarga el reporte PDF con los filtros seleccionados
    public function generarPdf(Request $request, string $tipo)
    {
        $tiposPermitidos = ['cumplimiento-zona', 'reprogramaciones-motivo', 'pendientes'];
        abort_unless(in_array($tipo, $tiposPermitidos, true), 404);

        $filtros = $this->validarFiltros($request);
        $filas = [];
        $total = 0;

        if ($tipo === 'cumplimiento-zona') {
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
                    'horario' => $tandeo->hora_inicio_programada . ' - ' . $tandeo->hora_fin_programada,
                ])
                ->values()
                ->all();

            $total = count($filas);
            $titulo = 'Resumen de tandeos pendientes de registrar';
        }

        $periodo = ($filtros['desde'] ?? 'Sin fecha inicial')
            . ' a '
            . ($filtros['hasta'] ?? 'Sin fecha final');
        $zona = isset($filtros['zona_id'])
            ? zonasModel::find($filtros['zona_id'])?->nombre
            : 'Todas las colonias';
        $circuito = isset($filtros['circuito_id'])
            ? circuitosModel::find($filtros['circuito_id'])?->nombre
            : 'Todos los circuitos';

        return Pdf::loadView('reportes.pdf', compact(
            'tipo',
            'titulo',
            'filas',
            'total',
            'periodo',
            'zona',
            'circuito'
        ))->setPaper('a4', 'landscape')
            ->download('reporte-' . $tipo . '-' . now()->format('Ymd-His') . '.pdf');
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
        ]);
    }

    // consulta los tandeos que cumplen con los filtros
    // Consulta los tandeos que coinciden con los filtros elegidos.
    // descripcion: consulta los tandeos que coinciden con los filtros elegidos.
    // entradas: lista de datos que se deben procesar
    // salida: devuelve los tandeos que cumplen con los filtros
    private function tandeosFiltrados(array $filtros)
    {
        return tandeoProgramado::with([
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