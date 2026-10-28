<?php

namespace App\Http\Controllers;

use App\Models\tandeoProgramado;
use App\Models\circuitosModel;
use App\Models\usuarioModel;
use App\Services\reprogramacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class reprogramacionController extends controller
{
    public function __construct(private reprogramacionService $servicio)
    {
    }

    public function previaManual(Request $request)
    {
        [$id, $inicio, $ajustes, $motivos, $motivo, $circuitoId, $tandeadorId, $duracionManual, $horasAtraso] = $this->datosManual($request);
        $resultado = $this->servicio->simularManual($id, $inicio, $ajustes, $motivos, $motivo, $circuitoId, $duracionManual);
        $desde = $inicio;
        $modo = 'manual';
        $origenCumplimiento = $request->boolean('origen_cumplimiento');
        $cumplimientoId = $request->input('cumplimiento_id');
        $cumplimientoObservaciones = $request->input('cumplimiento_observaciones', '');
        $tandeoId = $id;
        return view('reprogramaciones.previa', compact('desde', 'resultado', 'modo', 'tandeoId', 'circuitoId', 'tandeadorId', 'duracionManual', 'horasAtraso', 'origenCumplimiento', 'cumplimientoId', 'cumplimientoObservaciones'));
    }

    public function storeManual(Request $request)
    {
        [$id, $inicio, $ajustes, $motivos, $motivo, $circuitoId, $tandeadorId, $duracionManual, $horasAtraso] = $this->datosManual($request);
        $origenCumplimiento = $request->boolean('origen_cumplimiento');
        $horasCumplimiento = $origenCumplimiento ? $horasAtraso : null;
        $cumplimientoId = $origenCumplimiento && $request->filled('cumplimiento_id') ? (int) $request->cumplimiento_id : null;
        $observacionesCumplimiento = $origenCumplimiento ? $request->input('cumplimiento_observaciones') : null;
        $this->servicio->aplicarManual($id, $inicio, (int) auth()->user()->id, $ajustes, $motivos, $motivo, $circuitoId, $tandeadorId, $duracionManual, $horasCumplimiento, $cumplimientoId, $observacionesCumplimiento);
        $mensaje = $origenCumplimiento
            ? 'Reprogramación confirmada y atraso guardado en Cumplimiento.'
            : 'Horario reprogramado y tandeos afectados recorridos correctamente.';

        return redirect()->route('cumplimiento.index')->with('success', $mensaje);
    }

    private function datosManual(Request $request): array
    {
        $request->validate([
            'tandeo_id' => ['required', 'exists:tandeos_programados,id'],
            'circuito_id' => ['required', 'exists:circuitos,id'],
            'tandeador_id' => ['nullable', 'exists:usuarios,id'],
            'inicio_nuevo' => ['nullable', 'date'],
            'fecha_nueva' => ['nullable', 'date'],
            'hora_inicio_nueva' => ['nullable', 'date_format:H:i'],
            'hora_fin_nueva' => ['nullable', 'date_format:H:i'],
            'duracion_manual' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'horas_atraso' => ['nullable', 'integer', 'min:1', 'max:24'],
            'origen_cumplimiento' => ['nullable', 'boolean'],
            'cumplimiento_id' => ['nullable', 'exists:cumplimiento_tandeos,id'],
            'cumplimiento_observaciones' => ['nullable', 'string', 'max:1000'],
            'nuevo.*' => ['nullable', 'date'],
            'motivo_inicio' => ['nullable', 'string', 'max:255'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        $id = (int) $request->tandeo_id;
        [$ajustes, $motivos] = $this->leerAjustes($request);
        $circuitoId = (int) $request->circuito_id;
        $tandeadorId = $request->tandeador_id ? (int) $request->tandeador_id : null;

        if ($tandeadorId) {
            $circuito = circuitosModel::find($circuitoId);
            $asignado = $circuito && usuarioModel::whereKey($tandeadorId)
                ->where('rol', 'tandeador')
                ->whereHas('zonas', function ($consulta) use ($circuito) {
                    $consulta->where('zonas.id', $circuito->zona_id);
                })
                ->exists();

            if (!$asignado) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'tandeador_id' => 'El tandeador seleccionado no está asignado a la colonia de este circuito.',
                ]);
            }
        }

        $inicioTexto = $request->input("nuevo.$id") ?: $request->input('inicio_nuevo');
        if (! $inicioTexto && $request->filled('fecha_nueva') && $request->filled('hora_inicio_nueva')) {
            $inicioTexto = $request->fecha_nueva . ' ' . $request->hora_inicio_nueva;
        }
        if (! $inicioTexto) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'inicio_nuevo' => 'Indica la nueva fecha y hora del tandeo.',
            ]);
        }

        $horasAtraso = $request->boolean('origen_cumplimiento') && $request->filled('horas_atraso')
            ? (int) $request->input('horas_atraso')
            : null;
        $duracionManual = $request->input('duracion_manual');
        if ($duracionManual === null && $request->filled('fecha_nueva') && $request->filled('hora_fin_nueva')) {
            $finNuevo = Carbon::parse($request->fecha_nueva . ' ' . $request->hora_fin_nueva);
            $inicioNuevo = Carbon::parse($inicioTexto);

            if ($finNuevo->lessThanOrEqualTo($inicioNuevo)) {
                $finNuevo->addDay();
            }

            $duracionManual = (int) $inicioNuevo->diffInMinutes($finNuevo);
        }

        $motivo = trim((string) $request->input("motivo.$id", $request->input('motivo_inicio', 'Reprogramación manual')));
        unset($ajustes[$id]);
        unset($motivos[$id]);

        return [$id, Carbon::parse($inicioTexto), $ajustes, $motivos, $motivo ?: 'Reprogramación manual', $circuitoId, $tandeadorId, $duracionManual !== null ? (int) $duracionManual : null, $horasAtraso];
    }
    // Vista previa: calcula los movimientos pero NO guarda nada.
    // También se usa para "Recalcular" con los ajustes escritos a mano.
    public function previa(Request $request)
    {
        $request->validate([
            'desde'    => ['nullable', 'date'],
            'nuevo.*'  => ['nullable', 'date'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        $desde = $request->filled('desde')
            ? Carbon::parse($request->desde)
            : now()->startOfMinute();

        [$ajustes, $motivos] = $this->leerAjustes($request);

        $resultado = $this->servicio->simularApagon($desde, $ajustes, $motivos);

        $modo = 'apagon';
        $tandeoId = null;
        return view('reprogramaciones.previa', compact('desde', 'resultado', 'modo', 'tandeoId'));
    }

    // Confirmación: aplica los movimientos y guarda el historial
    public function store(Request $request)
    {
        $request->validate([
            'desde'    => ['required', 'date'],
            'nuevo.*'  => ['nullable', 'date'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        $desde = Carbon::parse($request->desde);

        [$ajustes, $motivos] = $this->leerAjustes($request);

        $this->servicio->aplicarApagon($desde, (int) auth()->user()->id, $ajustes, $motivos);

        return redirect()->route('cumplimiento.index')
            ->with('success', 'Apagón general registrado y tandeos reprogramados.');
    }

    public function previaFuga(Request $request)
    {
        $datos = $request->validate([
            'tipo_fuga' => ['required', 'in:laguna,pozo'],
            'horas_atraso' => ['required', 'integer', 'min:1', 'max:24'],
            'desde' => ['nullable', 'date'],
            'nuevo.*' => ['nullable', 'date'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : now()->startOfMinute();
        [$ajustes, $motivos] = $this->leerAjustes($request);
        $tipoFuga = $datos['tipo_fuga'];
        $horasAtraso = (int) $datos['horas_atraso'];
        $etiqueta = $tipoFuga === 'laguna' ? 'Fuga en laguna' : 'Fuga en pozo';

        $resultado = $this->servicio->simularApagon(
            $desde,
            $ajustes,
            $motivos,
            $tipoFuga,
            $horasAtraso,
            $etiqueta
        );

        $modo = 'fuga';
        $tandeoId = null;

        return view('reprogramaciones.previa', compact('desde', 'resultado', 'modo', 'tandeoId', 'tipoFuga', 'horasAtraso'));
    }

    public function storeFuga(Request $request)
    {
        $datos = $request->validate([
            'tipo_fuga' => ['required', 'in:laguna,pozo'],
            'horas_atraso' => ['required', 'integer', 'min:1', 'max:24'],
            'desde' => ['required', 'date'],
            'nuevo.*' => ['nullable', 'date'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        [$ajustes, $motivos] = $this->leerAjustes($request);

        $this->servicio->aplicarFuga(
            Carbon::parse($datos['desde']),
            (int) auth()->user()->id,
            $datos['tipo_fuga'],
            (int) $datos['horas_atraso'],
            $ajustes,
            $motivos
        );

        $nombre = $datos['tipo_fuga'] === 'laguna' ? 'Laguna' : 'Pozo';

        return redirect()->route('cumplimiento.index')
            ->with('success', "Fuga en {$nombre} registrada y tandeos afectados reprogramados.");
    }

    /**
     * Separa lo que la persona cambió a mano de lo que propuso el sistema.
     * Un horario cuenta como ajustado si es distinto al propuesto,
     * o si ya venía ajustado de un recálculo anterior.
     */
    private function leerAjustes(Request $request): array
    {
        $nuevos     = $request->input('nuevo', []);
        $propuestos = $request->input('propuesto', []);
        $previos    = $request->input('manual_prev', []);
        $textos     = $request->input('motivo', []);
        $bases      = $request->input('motivo_base', []);

        $ajustes = [];
        foreach ($nuevos as $id => $valor) {
            if (! $valor) {
                continue;
            }

            $cambio = $valor !== ($propuestos[$id] ?? null) || ! empty($previos[$id]);

            if ($cambio) {
                $ajustes[(int) $id] = Carbon::parse($valor);
            }
        }

        $motivos = [];
        foreach ($textos as $id => $texto) {
            $texto = trim((string) $texto);

            if ($texto !== '' && $texto !== ($bases[$id] ?? '')) {
                $motivos[(int) $id] = $texto;
            }
        }

        return [$ajustes, $motivos];
    }
}
