<?php

namespace App\Http\Controllers;

// archivo del sistema: archivo con acciones para las pantallas del sistema

use App\Models\TandeoProgramado;
use App\Models\ZonasModel;
use App\Services\RepeticionTandeoService;
use App\Services\ReprogramacionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarioController extends Controller
{
    // consulta y muestra los registros de la pantalla
    // Consulta los registros y prepara la pantalla de listado.
    // descripcion: consulta los registros y prepara la pantalla de listado.
    // entradas: no requiere datos externos
    // salida: muestra la pantalla con los datos consultados
    public function index()
    {
        $colonias = ZonasModel::where('activo', true)->orderBy('nombre')->get();

        return view('calendario.index', compact('colonias'));
    }

    // prepara los tandeos para mostrarlos en el calendario
    // Consulta y prepara los tandeos que aparecen en el calendario.
    // descripcion: consulta y prepara los tandeos que aparecen en el calendario.
    // entradas: solicitud con los datos enviados por el usuario; dato necesario para realizar el proceso
    // salida: devuelve los tandeos en formato JSON para mostrarlos en el calendario
    public function eventos(Request $request, RepeticionTandeoService $repeticiones)
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
            // Ordena los cumplimientos para consultar primero el registro más reciente.
            // descripcion: ordena los cumplimientos para consultar primero el registro más reciente.
            // entradas: cumplimientos relacionados con el tandeo
            // salida: devuelve los cumplimientos ordenados por fecha de captura
            'cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            },
        ])
            ->whereDate('fecha', '>=', $fechaInicio)
            ->whereDate('fecha', '<', $fechaFin)
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when($request->zona_id, function ($consulta) use ($request) {
                // Filtra los registros según la relación que deben cumplir.
                // descripcion: filtra los registros según la relación que deben cumplir.
                // entradas: consulta y registros relacionados
                // salida: añade la condición correspondiente a la consulta
                $consulta->whereHas('circuito', function ($circuitos) use ($request) {
                    $circuitos->where('zona_id', $request->zona_id);
                });
            })
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when(auth()->user()->rol === 'tandeador', function ($consulta) {
                // Agrega una condición para filtrar los registros consultados.
                // descripcion: agrega una condición para filtrar los registros consultados.
                // entradas: consulta que se está filtrando
                // salida: deja la condición agregada a la consulta
                $consulta->where(function ($filtro) {
                    $filtro->where('tandeador_id', auth()->user()->getKey())
                        // Filtra los registros según la relación que deben cumplir.
                        // descripcion: filtra los registros según la relación que deben cumplir.
                        // entradas: consulta y registros relacionados
                        // salida: añade la condición correspondiente a la consulta
                        ->orWhereHas('tandeadores', function ($personas) {
                            $personas->where('usuarios.id', auth()->user()->getKey());
                        });
                });
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada')
            ->get();

        // Prepara cada registro para guardarlo o mostrarlo.
        // descripcion: prepara cada registro para guardarlo o mostrarlo.
        // entradas: registro que se está transformando
        // salida: devuelve el registro con sus datos preparados
        $eventos = $tandeos->map(function ($tandeo) {
            $colonia = $tandeo->circuito->zona->nombre;
            $calles = $tandeo->circuito->descripcion ?: 'Sin lugar/tandeo registrado';
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
            } elseif ($cumplimiento) {
                $estado = str_replace(' ', '_', $cumplimiento->resultado);
            } elseif ($tandeo->estado !== 'programado') {
                $estado = str_replace(' ', '_', $tandeo->estado);
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
                'title' => $tandeo->circuito->nombre.' · '.$colonia,
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

    /**
     * Valida y guarda el nuevo horario de un tandeo movido en el calendario.
     *
     * @param  Request  $request  Identificador del tandeo y horario propuesto.
     * @param  ReprogramacionService  $reprogramaciones  Servicio que revisa empalmes y registra el movimiento.
     * @return JsonResponse Informa si el cambio se guardó o si existe un conflicto.
     */
    public function mover(Request $request, ReprogramacionService $reprogramaciones)
    {
        // primero se validan los datos recibidos en la solicitud,
        // asegurando que se reciba un tandeo válido y una nueva fecha y hora
        $datos = $request->validate([
            'tandeo_id' => 'required|exists:tandeos_programados,id',
            'inicio_nuevo' => 'required|date',
        ], [
            'tandeo_id.required' => 'No se recibió el tandeo que quieres mover.',
            'tandeo_id.exists' => 'El tandeo seleccionado ya no existe.',
            'inicio_nuevo.required' => 'Selecciona la nueva fecha y hora.',
            'inicio_nuevo.date' => 'La nueva fecha y hora no son válidas.',
        ]);

        // despues de validar los datos, se obtiene el tandeo a mover y se calcula la nueva fecha
        // y hora de inicio y fin
        $tandeoId = (int) $datos['tandeo_id'];
        $tandeo = TandeoProgramado::findOrFail($tandeoId);
        $inicioNuevo = Carbon::parse($datos['inicio_nuevo']);
        $duracion = (int) $tandeo->inicio->diffInMinutes($tandeo->fin);
        $finNuevo = $inicioNuevo->copy()->addMinutes($duracion);

        // se obtienen los otros tandeos que se empalman con el nuevo horario,
        // y si se encuentra alguno, se devuelve un mensaje de error indicando que no se puede guardar el cambio
        $otrosTandeos = TandeoProgramado::where('id', '!=', $tandeoId)
            ->whereDate('fecha', '>=', $inicioNuevo->copy()->subDay()->toDateString())
            ->whereDate('fecha', '<=', $finNuevo->toDateString())
            ->get();
        // se recorre la lista de otros tandeos y se verifica si hay empalmes con el nuevo horario
        foreach ($otrosTandeos as $otro) {
            if ($inicioNuevo < $otro->fin && $otro->inicio < $finNuevo) {
                return response()->json([
                    'empalme' => true,
                    'message' => 'No se puede guardar: el horario se empalma con otro tandeo. Elige otra fecha u hora.',
                ], 422);
            }
        }
        // si no hay empalmes, se simula el cambio y se verifica si hay movimientos
        // adicionales que impidan guardar el cambio
        $simulacion = $reprogramaciones->simularManual($tandeoId, $inicioNuevo);
        if (count($simulacion['movimientos']) > 1) {
            return response()->json([
                'empalme' => true,
                'message' => 'No se puede guardar porque el cambio recorrería otros tandeos. Elige otro horario.',
            ], 422);
        }
        // si no hay empalmes ni movimientos adicionales, se aplica el cambio y se guarda en la base de datos
        $reprogramaciones->aplicarManual(
            $tandeoId,
            $inicioNuevo,
            (int) auth()->user()->getKey(),
            // se pasan arreglos vacíos para los parámetros opcionales, ya que no se necesitan en este caso
            [],
            [],
            'Reprogramación desde el calendario'
        );

        // finalmente, se devuelve una respuesta indicando que el cambio se guardo correctamente
        return response()->json([
            'guardado' => true,
            'mensaje' => 'El horario del tandeo se actualizó correctamente.',
        ]);
    }
}
