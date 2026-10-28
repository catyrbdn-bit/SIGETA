<?php

namespace App\Http\Controllers;

use App\Models\TandeoProgramado;
use App\Models\zonasModel;
use App\Services\reprogramacionService;
use App\Services\repeticionTandeoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class calendarioController extends controller
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
            'cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            },
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
                    $filtro->where('tandeador_id', auth()->user()->getKey())
                        ->orWhereHas('tandeadores', function ($personas) {
                            $personas->where('usuarios.id', auth()->user()->getKey());
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

            $cumplimiento = $tandeo->cumplimientos->first();
            if ($cumplimiento && $cumplimiento->fecha_captura
                && Carbon::parse($cumplimiento->fecha_captura)->lt($tandeo->inicio)) {
                $cumplimiento = null;
            }

            if (now()->lt($tandeo->inicio)) {
                $estado = 'programado';
            } elseif ($tandeo->estado !== 'programado') {
                $estado = str_replace(' ', '_', $tandeo->estado);
            } elseif ($cumplimiento) {
                $estado = str_replace(' ', '_', $cumplimiento->resultado);
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

            $coloresEstados = [
                'programado' => ['fondo' => '#dbeafe', 'texto' => '#174ea6'],
                'en_curso' => ['fondo' => '#fff3cd', 'texto' => '#735c00'],
                'pendiente' => ['fondo' => '#e5e7eb', 'texto' => '#374151'],
                'cumplido' => ['fondo' => '#d1e7dd', 'texto' => '#146c43'],
                'atraso' => ['fondo' => '#ffe2c6', 'texto' => '#8a3d00'],
                'no_cumplido' => ['fondo' => '#f8d7da', 'texto' => '#842029'],
            ];

            $colores = $coloresEstados[$estado] ?? $coloresEstados['programado'];

            return [
                'id' => $tandeo->id,
                'title' => $tandeo->circuito->nombre . ' · ' . $colonia,
                'color' => $colores['fondo'],
                'contrastColor' => $colores['texto'],
                'start' => $tandeo->inicio->toIso8601String(),
                'end' => $tandeo->fin->toIso8601String(),
                'extendedProps' => [
                    'circuito' => $tandeo->circuito->nombre,
                    'circuitoId' => $tandeo->circuito_id,
                    'colonia' => $colonia,
                    'calles' => $calles,
                    'tandeadores' => $tandeadores,
                    'estado' => $estado,
                    'estadoTexto' => $nombresEstados[$estado] ?? ucfirst(str_replace('_', ' ', $estado)),
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

        $tandeoId = (int) $datos['tandeo_id'];
        $tandeo = TandeoProgramado::findOrFail($tandeoId);
        $inicioNuevo = Carbon::parse($datos['inicio_nuevo']);
        $duracion = (int) $tandeo->inicio->diffInMinutes($tandeo->fin);
        $finNuevo = $inicioNuevo->copy()->addMinutes($duracion);

        $otrosTandeos = TandeoProgramado::where('id', '!=', $tandeoId)
            ->whereDate('fecha', '>=', $inicioNuevo->copy()->subDay()->toDateString())
            ->whereDate('fecha', '<=', $finNuevo->toDateString())
            ->get();

        foreach ($otrosTandeos as $otro) {
            if ($inicioNuevo < $otro->fin && $otro->inicio < $finNuevo) {
                return response()->json([
                    'empalme' => true,
                    'message' => 'No se puede guardar: el horario se empalma con otro tandeo. Elige otra fecha u hora.',
                ], 422);
            }
        }

        $simulacion = $reprogramaciones->simularManual($tandeoId, $inicioNuevo);
        if (count($simulacion['movimientos']) > 1) {
            return response()->json([
                'empalme' => true,
                'message' => 'No se puede guardar porque el cambio recorrería otros tandeos. Elige otro horario.',
            ], 422);
        }

        $reprogramaciones->aplicarManual(
            $tandeoId,
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