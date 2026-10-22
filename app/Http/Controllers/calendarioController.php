<?php

namespace App\Http\Controllers;

use App\Models\TandeoProgramado;
use App\Models\zonasModel;
use App\Services\reprogramacionService;
use App\Services\repeticionTandeoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class calendarioController extends Controller
{
    public function index()
    {
        $colonias = zonasModel::where('activo', true)->orderBy('nombre')->get();

        return view('calendario.index', compact('colonias'));
    }

    public function eventos(Request $request, repeticionTandeoService $repeticiones)
    {
        $datos = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'zona_id' => 'nullable|integer|exists:zonas,id',
        ], [
            'start.required' => 'No se recibió la fecha inicial del calendario.',
            'start.date' => 'La fecha inicial del calendario no es válida.',
            'end.required' => 'No se recibió la fecha final del calendario.',
            'end.date' => 'La fecha final del calendario no es válida.',
            'zona_id.exists' => 'La colonia seleccionada no existe.',
        ]);

        $repeticiones->crearSiguientes();

        $fechaInicio = Carbon::parse(substr($datos['start'], 0, 10))->subDay()->toDateString();
        $fechaFin = substr($datos['end'], 0, 10);

        $tandeos = TandeoProgramado::with([
            'circuito.zona',
            'tandeadores',
            'tandeador',
        ])
            ->whereDate('fecha', '>=', $fechaInicio)
            ->whereDate('fecha', '<', $fechaFin)
            ->when($request->zona_id, function ($consulta) use ($request) {
                $consulta->whereHas('circuito', function ($circuitos) use ($request) {
                    $circuitos->where('zona_id', $request->zona_id);
                });
            })
            ->when(auth()->user()->rol === 'tandeador', function ($consulta) {
                $consulta->where(function ($filtro) {
                    $filtro->where('tandeador_id', auth()->id())
                        ->orWhereHas('tandeadores', function ($personas) {
                            $personas->where('usuarios.id', auth()->id());
                        });
                });
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada')
            ->get();

        $eventos = $tandeos->map(function ($tandeo) {
            $colonia = $tandeo->circuito->zona->nombre;
            $calles = $tandeo->circuito->descripcion ?: 'Sin calles registradas';
            $tandeadores = $tandeo->tandeadores->pluck('nombre')->implode(', ');

            if ($tandeadores === '') {
                $tandeadores = $tandeo->tandeador->nombre ?? 'Sin asignar';
            }

            return [
                'id' => $tandeo->id,
                'title' => $colonia . ' · ' . $calles,
                'start' => $tandeo->inicio->toIso8601String(),
                'end' => $tandeo->fin->toIso8601String(),
                'extendedProps' => [
                    'circuito' => $tandeo->circuito->nombre,
                    'circuitoId' => $tandeo->circuito_id,
                    'colonia' => $colonia,
                    'calles' => $calles,
                    'tandeadores' => $tandeadores,
                    'tandeadorId' => $tandeo->tandeador_id,
                ],
            ];
        });

        return response()->json($eventos);
    }

    public function mover(Request $request, reprogramacionService $reprogramaciones)
    {
        $datos = $request->validate([
            'tandeo_id' => 'required|exists:tandeos_programados,id',
            'inicio_nuevo' => 'required|date',
        ], [
            'tandeo_id.required' => 'No se recibió el tandeo que quieres mover.',
            'tandeo_id.exists' => 'El tandeo seleccionado ya no existe.',
            'inicio_nuevo.required' => 'Selecciona la nueva fecha y hora.',
            'inicio_nuevo.date' => 'La nueva fecha y hora no son válidas.',
        ]);

        $inicioNuevo = Carbon::parse($datos['inicio_nuevo']);
        $resultado = $reprogramaciones->simularManual((int) $datos['tandeo_id'], $inicioNuevo);

        if (count($resultado['movimientos']) > 1) {
            return response()->json([
                'requiere_previa' => true,
                'mensaje' => 'Este cambio afecta otros tandeos. Revisa la vista previa antes de guardar.',
            ]);
        }

        $reprogramaciones->aplicarManual(
            (int) $datos['tandeo_id'],
            $inicioNuevo,
            (int) auth()->user()->getKey(),
            [],
            [],
            'Reprogramación desde el calendario'
        );

        return response()->json([
            'guardado' => true,
            'mensaje' => 'El horario del tandeo se actualizó correctamente.',
        ]);
    }
}