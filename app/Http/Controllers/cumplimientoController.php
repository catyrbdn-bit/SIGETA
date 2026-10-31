<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CumplimientoTandeo;
use App\Models\tandeoProgramado;
use App\Models\zonasModel;
use App\Models\Reprogramacion;
use App\Services\reprogramacionService;
use App\Services\repeticionTandeoService;
use Carbon\Carbon;

class cumplimientoController extends controller
{
    public function index(repeticionTandeoService $repeticiones)
    {
        $repeticiones->crearSiguientes();

        $inicioSemana = now()->startOfWeek()->toDateString();
        $finSemana = now()->endOfWeek()->toDateString();

        $tandeos = tandeoProgramado::whereDate('fecha', '>=', $inicioSemana)
            ->whereDate('fecha', '<=', $finSemana)
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

        $zonas = zonasModel::where('activo', true)->get();

        return view('cumplimiento.index', compact('tandeos', 'zonas', 'cumplimientos'));
    }

    // Guarda los cambios de la tabla. Si un atraso nuevo provoca empalmes, primero muestra la vista previa.
    public function store(Request $request, reprogramacionService $servicio)
    {
        $datos = $request->validate([
            'cumplimientos' => ['required', 'array'],
            'cumplimientos.*.resultado' => ['nullable', 'in:cumplido,atraso,no_cumplido'],
            'cumplimientos.*.horas_atraso' => ['nullable', 'integer', 'min:1', 'max:24'],
            'cumplimientos.*.observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $ids = array_keys($datos['cumplimientos']);
        $tandeosValidos = tandeoProgramado::with('circuito')
            ->whereIn('id', $ids)
            ->whereBetween('fecha', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->get()
            ->keyBy('id');

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
                'cumplimientoId', 'cumplimientoObservaciones'
            ));
        }

        $cambios = [];

        DB::transaction(function () use ($datos, $tandeosValidos, $registros, &$cambios) {
            foreach ($datos['cumplimientos'] as $tandeoId => $fila) {
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
        
        return redirect()->route('cumplimiento.index')->with('success', 'Cambios de cumplimiento guardados correctamente.');
    }

    private function tieneAtrasoGuardado($registro): bool
    {
        return $registro
            && $registro->resultado === 'atraso'
            && $registro->motivo === 'otro';
    }

    // Botón "Apagón general": el registro/reprogramación se atiende en FN.8.
    public function apagonGeneral(Request $request)
    {
        $request->validate(['horas_atraso' => 'required|integer|min:1']);

        $tandeosHoy = tandeoProgramado::whereDate('fecha', now())
            ->where('estado', 'programado')
            ->get()
            ->filter(fn ($tandeo) => now()->greaterThanOrEqualTo($tandeo->inicio));

        foreach ($tandeosHoy as $tandeo) {
            CumplimientoTandeo::create([
                'tandeo_id' => $tandeo->id,
                'resultado' => 'atraso',
                'horas_atraso' => $request->horas_atraso,
                'motivo' => 'apagon_general',
                'capturado_por_id' => auth()->user()->getKey(),
                'fecha_captura' => now(),
            ]);
        }

        
        app(\App\Services\HistorialActividadService::class)->registrar(
            'apagon_general',
            'registrado',
            'Se registró un apagón general desde Gestión de cumplimiento.',
            ['horas_atraso' => (int) $request->horas_atraso, 'tandeos_afectados' => $tandeosHoy->pluck('id')->all()]
        );
        return redirect()->route('cumplimiento.index')
            ->with('success', 'Apagón general registrado; se aplicó a los tandeos cuyo horario ya había iniciado.');
    }

    // Botón "Fuga": afecta los tandeos de hoy solo en la zona indicada.
    public function fuga(Request $request)
    {
        $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'horas_atraso' => 'required|integer|min:1',
        ]);

        $tandeosZona = tandeoProgramado::whereDate('fecha', now())
            ->where('estado', 'programado')
            ->whereHas('circuito', function ($query) use ($request) {
                $query->where('zona_id', $request->zona_id);
            })
            ->get()
            ->filter(fn ($tandeo) => now()->greaterThanOrEqualTo($tandeo->inicio));

        foreach ($tandeosZona as $tandeo) {
            CumplimientoTandeo::create([
                'tandeo_id' => $tandeo->id,
                'resultado' => 'atraso',
                'horas_atraso' => $request->horas_atraso,
                'motivo' => 'fuga',
                'capturado_por_id' => auth()->user()->getKey(),
                'fecha_captura' => now(),
            ]);
        }

        app(\App\Services\HistorialActividadService::class)->registrar(
            'fuga',
            'registrada',
            'Se registró una fuga desde Gestión de cumplimiento.',
            ['zona_id' => (int) $request->zona_id, 'horas_atraso' => (int) $request->horas_atraso, 'tandeos_afectados' => $tandeosZona->pluck('id')->all()]
        );
        return redirect()->route('cumplimiento.index')
            ->with('success', 'Fuga registrada; se aplicó a los tandeos de la colonia seleccionada cuyo horario ya había iniciado.');
    }
}





