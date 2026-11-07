<?php

namespace App\Http\Controllers;
// archivo del sistema: archivo con acciones para las pantallas del sistema

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CumplimientoTandeo;
use App\Models\TandeoProgramado;
use App\Models\ZonasModel;
use App\Models\Reprogramacion;
use App\Services\ReprogramacionService;
use App\Services\RepeticionTandeoService;
use Carbon\Carbon;

class CumplimientoController extends Controller
{

    /**
     * Prepara el listado semanal de tandeos y sus registros de cumplimiento.
     *
     * @param RepeticionTandeoService $repeticiones Servicio que completa la lista de tandeos repetidos.
     * @return \Illuminate\Contracts\View\View Muestra la tabla de cumplimiento.
     */
    public function index(Request $request, RepeticionTandeoService $repeticiones)
    {
        $repeticiones->crearSiguientes();

        $periodo = $request->input('periodo', 'semana');
        if (!in_array($periodo, ['mes', 'semana', 'dia'], true)) {
            $periodo = 'semana';
        }

        $fechaConsulta = $request->input('fecha', now()->toDateString());
        if (!is_string($fechaConsulta) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaConsulta)
            || !Carbon::hasFormat($fechaConsulta, 'Y-m-d')) {
            $fechaConsulta = now()->toDateString();
        }

        $fechaReferencia = Carbon::createFromFormat('!Y-m-d', $fechaConsulta);
        $inicioPeriodo = $fechaReferencia->copy();
        $finPeriodo = $fechaReferencia->copy();

        if ($periodo === 'mes') {
            $inicioPeriodo->startOfMonth();
            $finPeriodo->endOfMonth();
        } elseif ($periodo === 'semana') {
            $inicioPeriodo->startOfWeek(Carbon::MONDAY);
            $finPeriodo->endOfWeek(Carbon::SUNDAY);
        }

        $fechaInicio = $inicioPeriodo->toDateString();
        $fechaFin = $finPeriodo->toDateString();
        $busqueda = trim((string) $request->input('buscar', ''));

        $consultaTandeos = TandeoProgramado::query();
        if ($periodo === 'dia') {
            $fechaAnterior = $fechaReferencia->copy()->subDay()->toDateString();
            $consultaTandeos->where(function ($consulta) use ($fechaInicio, $fechaAnterior) {
                $consulta->whereDate('fecha', $fechaInicio)
                    ->orWhere(function ($tandeosNocturnos) use ($fechaAnterior) {
                        $tandeosNocturnos->whereDate('fecha', $fechaAnterior)
                            ->whereColumn('hora_fin_programada', '<=', 'hora_inicio_programada');
                    });
            });
        } else {
            $consultaTandeos->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        }

        $tandeos = $consultaTandeos
            ->with('circuito.zona')
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada')
            ->get();

        $cumplimientos = CumplimientoTandeo::whereIn('tandeo_id', $tandeos->pluck('id'))
            ->orderByDesc('fecha_captura')
            ->orderByDesc('id')
            ->get()
            ->unique('tandeo_id')
            ->keyBy('tandeo_id');

        $zonas = ZonasModel::where('activo', true)->get();

        return view('cumplimiento.index', compact(
            'tandeos', 'zonas', 'cumplimientos', 'periodo', 'fechaConsulta',
            'fechaReferencia', 'inicioPeriodo', 'finPeriodo', 'busqueda'
        ));
    }

    /**
     * Guarda el estado, el atraso y las observaciones de los tandeos.
     * Si un atraso causa empalmes, prepara la vista previa de la reprogramación.
     *
     * @param Request $request Estados, horas de atraso y observaciones capturados.
     * @param ReprogramacionService $servicio Servicio que revisa y calcula los empalmes.
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse Muestra una vista previa o regresa al listado.
     */
    public function store(Request $request, ReprogramacionService $servicio)
    {
        $datos = $request->validate([
            'cumplimientos' => ['required', 'array'],
            'cumplimientos.*.resultado' => ['nullable', 'in:cumplido,atraso,no_cumplido'],
            'cumplimientos.*.horas_atraso' => ['nullable', 'integer', 'min:1', 'max:24'],
            'cumplimientos.*.observaciones' => ['nullable', 'string', 'max:1000'],
            'periodo' => ['nullable', 'in:mes,semana,dia'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $periodo = $datos['periodo'] ?? 'semana';
        $fechaConsulta = $datos['fecha'] ?? now()->toDateString();
        $fechaReferencia = Carbon::createFromFormat('!Y-m-d', $fechaConsulta);
        $inicioPeriodo = $fechaReferencia->copy();
        $finPeriodo = $fechaReferencia->copy();

        if ($periodo === 'mes') {
            $inicioPeriodo->startOfMonth();
            $finPeriodo->endOfMonth();
        } elseif ($periodo === 'semana') {
            $inicioPeriodo->startOfWeek(Carbon::MONDAY);
            $finPeriodo->endOfWeek(Carbon::SUNDAY);
        }

        $ids = array_keys($datos['cumplimientos']);
        $inicioConsulta = $inicioPeriodo->toDateString();
        $finConsulta = $finPeriodo->toDateString();
        $consultaTandeosValidos = TandeoProgramado::with('circuito')
            ->whereIn('id', $ids);

        if ($periodo === 'dia') {
            $fechaAnterior = $fechaReferencia->copy()->subDay()->toDateString();
            $consultaTandeosValidos->where(function ($consulta) use ($inicioConsulta, $fechaAnterior) {
                $consulta->whereDate('fecha', $inicioConsulta)
                    ->orWhere(function ($tandeosNocturnos) use ($fechaAnterior) {
                        $tandeosNocturnos->whereDate('fecha', $fechaAnterior)
                            ->whereColumn('hora_fin_programada', '<=', 'hora_inicio_programada');
                    });
            });
        } else {
            $consultaTandeosValidos->whereBetween('fecha', [$inicioConsulta, $finConsulta]);
        }

        $tandeosValidos = $consultaTandeosValidos->get()->keyBy('id');

        $registros = CumplimientoTandeo::whereIn('tandeo_id', $ids)
            ->orderByDesc('fecha_captura')
            ->orderByDesc('id')
            ->get()
            ->unique('tandeo_id')
            ->keyBy('tandeo_id');

        $atrasosNuevos = [];
        foreach ($datos['cumplimientos'] as $id => $fila) {
            $horas = $fila['horas_atraso'] ?? null;
            if ($horas === null || $horas === '') {
                continue;
            }

            $registro = $registros->get((int) $id);
            if (!$registro || $registro->resultado !== 'atraso' || (int) $registro->horas_atraso !== (int) $horas) {
                $atrasosNuevos[(int) $id] = (int) $horas;
            }
        }

        $atrasosConEmpalme = [];
        $iniciosAtraso = [];
        foreach ($atrasosNuevos as $id => $horas) {
            $tandeo = $tandeosValidos->get($id);
            if (!$tandeo) {
                continue;
            }

            $registro = $registros->get($id);
            if (now()->lt($tandeo->inicio) && !$this->tieneAtrasoGuardado($registro)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "cumplimientos.$id.resultado" => 'No se puede registrar un resultado antes del horario programado.',
                ]);
            }

            // Si ya hubo un recorrido confirmado, el nuevo valor reemplaza el atraso anterior.
            $horasMover = $horas;
            if ($this->tieneAtrasoGuardado($registro)) {
                $ultimoMovimiento = Reprogramacion::where('tandeo_id', $id)->orderByDesc('id')->first();
                if ($ultimoMovimiento && Carbon::parse($ultimoMovimiento->inicio_nuevo)->equalTo($tandeo->inicio)) {
                    $horasMover -= (int) $registro->horas_atraso;
                }
            }

            $inicioNuevo = $tandeo->inicio->copy()->addHours($horasMover);
            $iniciosAtraso[$id] = $inicioNuevo;
            $duracion = (int) $tandeo->inicio->diffInMinutes($tandeo->fin);
            $resultado = $servicio->simularManual(
                $id,
                $inicioNuevo,
                [],
                [],
                'Atraso registrado desde Cumplimiento.',
                (int) $tandeo->circuito_id,
                $duracion
            );

            if (count($resultado['movimientos']) > 1) {
                $atrasosConEmpalme[$id] = [
                    'resultado' => $resultado,
                    'inicio' => $inicioNuevo,
                ];
            }
        }

        if (count($atrasosConEmpalme) > 1 || (count($atrasosConEmpalme) === 1 && count($atrasosNuevos) > 1)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cumplimientos' => 'Guarda un atraso que requiere recorrido a la vez. Después puedes registrar los demás cambios.',
            ]);
        }

        $atrasosSinEmpalme = [];
        if (count($atrasosConEmpalme) === 0) {
            foreach ($atrasosNuevos as $idAtraso => $horasAtraso) {
                $atrasosSinEmpalme[$idAtraso] = [
                'id' => $idAtraso,
                'horas' => $horasAtraso,
                'inicio' => $iniciosAtraso[$idAtraso],
                ];
            }
        }

        if (count($atrasosConEmpalme) === 1) {
            $id = (int) array_key_first($atrasosConEmpalme);
            $fila = $datos['cumplimientos'][$id] ?? [];
            $registro = $registros->get($id);

            // La vista previa interrumpe el guardado; no dejamos cambios de otras filas sin guardar en silencio.
            foreach ($datos['cumplimientos'] as $otroId => $otraFila) {
                if ((int) $otroId === $id) continue;
                $otroRegistro = $registros->get((int) $otroId);
                $resultadoEnviado = $otraFila['resultado'] ?? $otroRegistro?->resultado;
                $horasEnviadas = $otraFila['horas_atraso'] ?? $otroRegistro?->horas_atraso;
                $observacionesEnviadas = $otraFila['observaciones'] ?? $otroRegistro?->observaciones;
                if ($resultadoEnviado !== ($otroRegistro?->resultado)
                    || (string) ($horasEnviadas ?? '') !== (string) ($otroRegistro?->horas_atraso ?? '')
                    || (string) ($observacionesEnviadas ?? '') !== (string) ($otroRegistro?->observaciones ?? '')) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cumplimientos' => 'Guarda primero los cambios de las otras filas; luego registra el atraso que requiere vista previa.',
                    ]);
                }
            }

            $tandeo = $tandeosValidos->get($id);
            $desde = $atrasosConEmpalme[$id]['inicio'];
            $resultado = $atrasosConEmpalme[$id]['resultado'];
            $modo = 'manual';
            $tandeoId = $id;
            $circuitoId = (int) $tandeo->circuito_id;
            $tandeadorId = $tandeo->tandeador_id;
            $duracionManual = (int) $tandeo->inicio->diffInMinutes($tandeo->fin);
            $horasAtraso = (int) $fila['horas_atraso'];
            $origenCumplimiento = true;
            $cumplimientoId = $registro?->id;
            $cumplimientoObservaciones = $fila['observaciones'] ?? $registro?->observaciones ?? '';

            return view('reprogramaciones.previa', compact(
                'desde', 'resultado', 'modo', 'tandeoId', 'circuitoId', 'tandeadorId',
                'duracionManual', 'horasAtraso', 'origenCumplimiento',
                'cumplimientoId', 'cumplimientoObservaciones', 'periodo', 'fechaConsulta'
            ));
        }

        $cambios = [];

        // Agrupa los cambios para guardarlos como una sola operación.
        // descripcion: agrupa los cambios para guardarlos como una sola operación.
        // entradas: registros que se actualizan durante la operación
        // salida: confirma los cambios o los revierte si ocurre un error
        DB::transaction(function () use ($datos, $tandeosValidos, $registros, $servicio, $atrasosSinEmpalme, &$cambios) {
            foreach ($atrasosSinEmpalme as $atrasoSinEmpalme) {
                $idAtraso = $atrasoSinEmpalme['id'];
                $tandeoAtrasado = $tandeosValidos->get($idAtraso);
                $registroAtrasado = $registros->get($idAtraso);
                $filaAtraso = $datos['cumplimientos'][$idAtraso];
                $duracion = (int) $tandeoAtrasado->inicio->diffInMinutes($tandeoAtrasado->fin);

                $comprobacion = $servicio->simularManual(
                    $idAtraso,
                    $atrasoSinEmpalme['inicio'],
                    [],
                    [],
                    'Atraso registrado desde Cumplimiento.',
                    (int) $tandeoAtrasado->circuito_id,
                    $duracion
                );

                if (count($comprobacion['movimientos']) > 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cumplimientos' => 'Un atraso provoca empalmes con otro cambio. Guarda ese atraso por separado para revisar la vista previa.',
                    ]);
                }

                $servicio->aplicarManual(
                    $idAtraso,
                    $atrasoSinEmpalme['inicio'],
                    (int) auth()->user()->getKey(),
                    [],
                    [],
                    'Atraso registrado desde Cumplimiento.',
                    (int) $tandeoAtrasado->circuito_id,
                    $tandeoAtrasado->tandeador_id,
                    $duracion,
                    $atrasoSinEmpalme['horas'],
                    $registroAtrasado?->id,
                    $filaAtraso['observaciones'] ?? $registroAtrasado?->observaciones
                );

                $cambios[] = [
                    'tandeo_id' => $idAtraso,
                    'circuito' => $tandeoAtrasado->circuito->nombre,
                    'antes' => $registroAtrasado?->only(['resultado', 'horas_atraso', 'observaciones']),
                    'despues' => [
                        'resultado' => 'atraso',
                        'horas_atraso' => $atrasoSinEmpalme['horas'],
                        'observaciones' => $filaAtraso['observaciones'] ?? null,
                    ],
                ];
            }

            foreach ($datos['cumplimientos'] as $tandeoId => $fila) {
                if (isset($atrasosSinEmpalme[(int) $tandeoId])) {
                    continue;
                }

                $tandeo = $tandeosValidos->get((int) $tandeoId);
                if (!$tandeo) continue;

                $resultado = $fila['resultado'] ?? null;
                $horasAtraso = $fila['horas_atraso'] ?? null;
                if ($horasAtraso !== null && $horasAtraso !== '') $resultado = 'atraso';
                if (!$resultado) continue;

                if (now()->lt($tandeo->inicio) && !$this->tieneAtrasoGuardado($registros->get((int) $tandeoId))) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "cumplimientos.$tandeoId.resultado" => 'No se puede registrar un resultado antes del horario programado.',
                    ]);
                }
                if ($resultado === 'atraso' && !$horasAtraso) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "cumplimientos.$tandeoId.horas_atraso" => 'Indica cuántas horas de atraso tiene este tandeo.',
                    ]);
                }
                if ($resultado !== 'atraso') $horasAtraso = null;

                $valores = [
                    'resultado' => $resultado,
                    'horas_atraso' => $horasAtraso,
                    'observaciones' => $fila['observaciones'] ?? null,
                    'capturado_por_id' => auth()->user()->getKey(),
                    'fecha_captura' => now(),
                ];
                $registro = $registros->get((int) $tandeoId);

                // No vuelve a guardar filas que conservan los mismos datos
                $mismosDatos = $registro
                    && $registro->resultado === $resultado
                    && (string) ($registro->horas_atraso ?? '') === (string) ($horasAtraso ?? '')
                    && trim((string) ($registro->observaciones ?? '')) === trim((string) ($valores['observaciones'] ?? ''));

                if ($mismosDatos) continue;

                $antes = $registro?->only(['resultado', 'horas_atraso', 'observaciones']);

                if ($registro) $registro->update($valores);
                else CumplimientoTandeo::create(array_merge($valores, ['tandeo_id' => $tandeoId]));
                $cambios[] = ['tandeo_id' => (int) $tandeoId, 'circuito' => $tandeo->circuito->nombre, 
                'antes' => $antes, 'despues' => $valores];
            }
        });

        if ($cambios) {
            app(\App\Services\HistorialActividadService::class)->registrar(
                'cumplimiento',
                'actualizacion',
                'Se guardaron cambios en el cumplimiento de tandeos.',
                ['registros' => $cambios]
            );
        }
        
        if (!$cambios) {
            return redirect()->route('cumplimiento.index', ['periodo' => $periodo, 'fecha' => $fechaConsulta])
                ->with('warning', 'No se guardaron cambios. Selecciona un estado disponible y vuelve a intentarlo.');
        }

        return redirect()->route('cumplimiento.index', ['periodo' => $periodo, 'fecha' => $fechaConsulta])
            ->with('success', 'Cambios de cumplimiento guardados correctamente.');
    }

    public function destroy(Request $request, $id)
    {
        $registro = CumplimientoTandeo::with('tandeo.circuito')->findOrFail($id);
        $tandeo = $registro->tandeo;

        $detalles = [
            'tandeo_id' => $tandeo->id,
            'circuito' => $tandeo->circuito->nombre ?? 'Sin circuito',
            'resultado' => $registro->resultado,
            'horas_atraso' => $registro->horas_atraso,
            'motivo' => $registro->motivo,
            'observaciones' => $registro->observaciones,
        ];

        DB::transaction(function () use ($registro, $detalles) {
            $registro->delete();

            app(\App\Services\HistorialActividadService::class)->registrar(
                'cumplimiento',
                'eliminacion',
                'Se eliminó una captura de cumplimiento.',
                $detalles
            );
        });

        $periodo = in_array($request->input('periodo'), ['mes', 'semana', 'dia'], true)
            ? $request->input('periodo')
            : 'semana';
        $fecha = $request->input('fecha', now()->toDateString());

        return redirect()->route('cumplimiento.index', ['periodo' => $periodo, 'fecha' => $fecha])
            ->with('success', 'Se eliminó la captura de cumplimiento. El tandeo y su horario permanecen.');
    }
    // revisa si el tandeo ya tiene un atraso registrado
    // Comprueba si el cumplimiento del tandeo ya tiene un atraso registrado.
    // descripcion: comprueba si el cumplimiento del tandeo ya tiene un atraso registrado.
    // entradas: registro que se va a procesar
    // salida: indica si existe un atraso guardado
    private function tieneAtrasoGuardado($registro): bool
    {
        return $registro
            && $registro->resultado === 'atraso'
            && $registro->motivo === 'otro';
    }


}





