<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TandeoProgramado;
use App\Models\TandeoRepeticion;
use App\Models\circuitosModel;
use App\Models\usuarioModel;
use App\Models\zonasModel;
use App\Models\cumplimientoTandeo;
use App\Models\Reprogramacion;
use App\Services\repeticionTandeoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class tandeosController extends Controller
{
    public function index(repeticionTandeoService $repeticiones)
    {
        $repeticiones->crearSiguientes();

        $tandeos = TandeoProgramado::with([
            'circuito.zona',
            'tandeador',
            'cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            },
        ])
            ->when(auth()->user()->rol === 'tandeador', function ($consulta) {
                $consulta->where('tandeador_id', auth()->id());
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada')
            ->get();

        $zonas = zonasModel::where('activo', true)->orderBy('nombre')->get();

        return view('tandeos.index', compact('tandeos', 'zonas'));
    }

    public function create()
    {
        $zonas = zonasModel::where('activo', true)
            ->orderBy('nombre')
            ->get();

        $circuitos = circuitosModel::with('zona')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $tandeadores = usuarioModel::where('rol', 'tandeador')
            ->orderBy('nombre')
            ->get();

        return view('tandeos.create', compact('zonas', 'circuitos', 'tandeadores'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'zona_id' => ['required', 'exists:zonas,id'],
            'circuito_id' => ['required', 'exists:circuitos,id'],
            'fecha' => ['required', 'date'],
            'hora_inicio_programada' => ['required', 'date_format:H:i'],
            'duracion' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'tandeador_id' => ['nullable', 'exists:usuarios,id'],
            'repetir' => ['nullable', 'boolean'],
            'cada_dias' => ['required_if:repetir,1', 'nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $zonaActiva = zonasModel::whereKey($datos['zona_id'])
            ->where('activo', true)
            ->exists();

        $circuito = circuitosModel::whereKey($datos['circuito_id'])
            ->where('zona_id', $datos['zona_id'])
            ->where('activo', true)
            ->first();

        if (! $zonaActiva || ! $circuito) {
            throw ValidationException::withMessages([
                'circuito_id' => 'Selecciona un circuito activo que pertenezca a la zona elegida.',
            ]);
        }

        $horaInicio = Carbon::parse(
            $datos['fecha'] . ' ' . $datos['hora_inicio_programada']
        );

        $fechas = [$horaInicio->copy()];
        $serieId = null;

        if (! empty($datos['repetir'])) {
            $cadaDias = (int) $datos['cada_dias'];
            $serieId = (string) Str::uuid();
            $fechaLimite = now()->addDays((int) config('sigeta.ventana_dias', 12))->endOfDay();
            if ($horaInicio->greaterThan(now())) {
                $fechaLimite = $horaInicio->copy()->addDays((int) config('sigeta.ventana_dias', 12))->endOfDay();
            }
            $fechaSiguiente = $horaInicio->copy();

            while (true) {
                $fechaSiguiente->addDays($cadaDias);
                if ($fechaSiguiente->greaterThan($fechaLimite)) {
                    break;
                }
                $fechas[] = $fechaSiguiente->copy();
            }
        }

        $duracionMinutos = (int) round((float) $datos['duracion'] * 60);

        DB::transaction(function () use ($fechas, $circuito, $horaInicio, $duracionMinutos, $datos, $serieId) {
            if ($serieId) {
                TandeoRepeticion::create([
                    'repeticion_uuid' => $serieId,
                    'circuito_id' => $circuito->id,
                    'fecha_ancla' => $horaInicio->toDateString(),
                    'hora_inicio' => $horaInicio->format('H:i:s'),
                    'duracion_minutos' => $duracionMinutos,
                    'tandeador_id' => $datos['tandeador_id'] ?? null,
                    'cada_dias' => (int) $datos['cada_dias'],
                    'activa' => true,
                ]);
            }

            foreach ($fechas as $fecha) {
                $inicio = $fecha->copy()->setTimeFromTimeString($horaInicio->format('H:i:s'));
                $this->validarHorarioUnico($circuito->id, $inicio, $serieId);
                $horaFin = $inicio->copy()->addMinutes($duracionMinutos);

                TandeoProgramado::create([
                    'circuito_id' => $circuito->id,
                    'fecha' => $inicio->toDateString(),
                    'hora_inicio_programada' => $inicio->format('H:i'),
                    'hora_fin_programada' => $horaFin->format('H:i'),
                    'estado' => 'programado',
                    'tandeador_id' => $datos['tandeador_id'] ?? null,
                    'repeticion_uuid' => $serieId,
                    'repetir_cada_dias' => $serieId ? (int) $datos['cada_dias'] : null,
                    'fecha_repeticion' => $serieId ? $inicio->toDateString() : null,
                ]);
            }
        });

        $mensaje = $serieId
            ? '¡Se inició la repetición cada ' . (int) $datos['cada_dias'] . ' días! El sistema seguirá agregando los próximos tandeos.'
            : '¡Tandeo programado exitosamente!';

        return redirect()->route('tandeos.index')->with('success', $mensaje);
    }

    public function edit($id)
    {
        $tandeo = TandeoProgramado::with('circuito')->findOrFail($id);

        $circuitos = circuitosModel::with('zona')->where('activo', true)->get();

        $tandeadores = usuarioModel::where('rol', 'tandeador')->get();

        return view('tandeos.edit', compact('tandeo', 'circuitos', 'tandeadores'));
    }

    public function destroy($id)
    {
        $tandeo = TandeoProgramado::findOrFail($id);
        $serie = $tandeo->repeticion_uuid
            ? TandeoRepeticion::where('repeticion_uuid', $tandeo->repeticion_uuid)->first()
            : null;
        $tandeos = $tandeo->repeticion_uuid
            ? TandeoProgramado::where('repeticion_uuid', $tandeo->repeticion_uuid)->get()
            : collect([$tandeo]);
        $ids = $tandeos->pluck('id');

        DB::transaction(function () use ($serie, $tandeos, $ids) {
            if ($serie && $serie->activa) {
                $serie->update(['activa' => false]);
            }

            cumplimientoTandeo::whereIn('tandeo_id', $ids)->get()
                ->each(fn ($cumplimiento) => $cumplimiento->delete());

            Reprogramacion::whereIn('tandeo_id', $ids)->get()
                ->each(fn ($reprogramacion) => $reprogramacion->delete());

            $tandeos->each(fn ($ocurrencia) => $ocurrencia->delete());
        });

        $mensaje = $tandeo->repeticion_uuid
            ? 'Se eliminó la repetición completa y sus registros relacionados.'
            : '¡Tandeo eliminado exitosamente!';

        return redirect()->route('tandeos.index')
            ->with('success', $mensaje);
    }

    private function validarHorarioUnico(int $circuitoId, Carbon $nuevoInicio, ?string $ignorarSerieId = null): void
    {
        $tandeos = TandeoProgramado::where('circuito_id', $circuitoId)
            ->whereDate('fecha', $nuevoInicio->toDateString())
            ->get();

        $duplicado = $tandeos->contains(fn ($tandeo) =>
            Carbon::parse($tandeo->fecha->format('Y-m-d') . ' ' . $tandeo->hora_inicio_programada)
                ->format('H:i') === $nuevoInicio->format('H:i')
        );

        if (! $duplicado) {
            $series = TandeoRepeticion::where('activa', true)
                ->when($ignorarSerieId, fn ($consulta) => $consulta->where('repeticion_uuid', '!=', $ignorarSerieId))
                ->get();

            foreach ($series as $serie) {
                $inicioAncla = Carbon::parse($serie->fecha_ancla->format('Y-m-d') . ' ' . $serie->hora_inicio);
                $intervalo = (int) $serie->cada_dias;

                if ((int) $serie->circuito_id !== $circuitoId
                    || $inicioAncla->format('H:i') !== $nuevoInicio->format('H:i')
                    || $intervalo < 1
                    || $nuevoInicio->lessThanOrEqualTo($inicioAncla)) {
                    continue;
                }

                $dias = $inicioAncla->copy()->startOfDay()->diffInDays($nuevoInicio->copy()->startOfDay());
                if ($dias % $intervalo === 0) {
                    $duplicado = true;
                    break;
                }
            }
        }

        if ($duplicado) {
            throw ValidationException::withMessages([
                'fecha' => 'Ya existe un tandeo de este circuito con la misma fecha y hora de inicio.',
            ]);
        }
    }
}
