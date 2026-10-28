<?php

namespace App\Http\Controllers;

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
                ->map(function ($tandeo) {
                    return [
                        'zona' => $tandeo->circuito->zona->nombre,
                        'resultado' => $tandeo->cumplimientos->first()?->resultado,
                    ];
                })
                ->filter(fn ($registro) => $registro['resultado'] !== null);

            $filas = $registros
                ->groupBy('zona')
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
                ->when($filtros['circuito_id'] ?? null, function ($consulta, $circuitoId) {
                    $consulta->whereHas('tandeo', fn ($tandeos) => $tandeos->where('circuito_id', $circuitoId));
                })
                ->when($filtros['zona_id'] ?? null, function ($consulta, $zonaId) {
                    $consulta->whereHas('tandeo.circuito', fn ($circuitos) => $circuitos->where('zona_id', $zonaId));
                })
                ->when($filtros['desde'] ?? null, fn ($consulta, $desde) => $consulta->whereDate('created_at', '>=', $desde))
                ->when($filtros['hasta'] ?? null, fn ($consulta, $hasta) => $consulta->whereDate('created_at', '<=', $hasta))
                ->get();

            $filas = $consulta
                ->groupBy('tipo_origen')
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
                ->filter(fn ($tandeo) => $tandeo->fin->isPast() && $tandeo->cumplimientos->isEmpty())
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

    public function cumplimientoCircuitoExcel(Request $request)
    {
        $filtros = $this->validarFiltros($request);
        $tandeos = $this->tandeosFiltrados($filtros)->get();

        $filas = $tandeos
            ->groupBy('circuito_id')
            ->map(function ($tandeosCircuito) {
                $primerTandeo = $tandeosCircuito->first();
                $resultados = $tandeosCircuito->map(
                    fn ($tandeo) => $tandeo->cumplimientos->first()?->resultado
                );

                return [
                    $primerTandeo->circuito->nombre,
                    $primerTandeo->circuito->zona->nombre,
                    $primerTandeo->circuito->descripcion,
                    $tandeosCircuito->count(),
                    $resultados->filter(fn ($resultado) => in_array($resultado, ['cumplido', 'atraso'], true))->count(),
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

    private function validarFiltros(Request $request): array
    {
        return $request->validate([
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'circuito_id' => ['nullable', 'integer', 'exists:circuitos,id'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);
    }

    private function tandeosFiltrados(array $filtros)
    {
        return tandeoProgramado::with([
            'circuito.zona',
            'cumplimientos' => fn ($consulta) => $consulta
                ->orderByDesc('fecha_captura')
                ->orderByDesc('id'),
        ])
            ->when($filtros['zona_id'] ?? null, function ($consulta, $zonaId) {
                $consulta->whereHas('circuito', fn ($circuitos) => $circuitos->where('zona_id', $zonaId));
            })
            ->when($filtros['circuito_id'] ?? null, fn ($consulta, $circuitoId) => $consulta->where('circuito_id', $circuitoId))
            ->when($filtros['desde'] ?? null, fn ($consulta, $desde) => $consulta->whereDate('fecha', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($consulta, $hasta) => $consulta->whereDate('fecha', '<=', $hasta))
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada');
    }

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