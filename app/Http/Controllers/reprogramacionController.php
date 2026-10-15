<?php

namespace App\Http\Controllers;

use App\Services\reprogramacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class reprogramacionController extends Controller
{
    public function __construct(private reprogramacionService $servicio)
    {
    }

    public function createManual(int $id)
    {
        $tandeo = \App\Models\tandeoProgramado::with('circuito.zona', 'circuito.puntoAbastecimiento')->findOrFail($id);
        return view('reprogramaciones.manual', compact('tandeo'));
    }

    public function previaManual(Request $request)
    {
        [$id, $inicio, $ajustes, $motivos, $motivo] = $this->datosManual($request);
        $resultado = $this->servicio->simularManual($id, $inicio, $ajustes, $motivos, $motivo);
        $desde = $inicio;
        $modo = 'manual';
        $tandeoId = $id;
        return view('reprogramaciones.previa', compact('desde', 'resultado', 'modo', 'tandeoId'));
    }

    public function storeManual(Request $request)
    {
        [$id, $inicio, $ajustes, $motivos, $motivo] = $this->datosManual($request);
        $this->servicio->aplicarManual($id, $inicio, (int) auth()->user()->id, $ajustes, $motivos, $motivo);
        return redirect()->route('cumplimiento.index')
            ->with('success', 'Horario reprogramado y tandeos afectados recorridos correctamente.');
    }

    private function datosManual(Request $request): array
    {
        $request->validate([
            'tandeo_id' => ['required', 'exists:tandeos_programados,id'],
            'inicio_nuevo' => ['nullable', 'date'],
            'nuevo.*' => ['nullable', 'date'],
            'motivo_inicio' => ['nullable', 'string', 'max:255'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        $id = (int) $request->tandeo_id;
        [$ajustes, $motivos] = $this->leerAjustes($request);
        $inicioTexto = $request->input("nuevo.$id") ?: $request->input('inicio_nuevo');
        if (! $inicioTexto) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'inicio_nuevo' => 'Indica la nueva fecha y hora del tandeo.',
            ]);
        }

        $motivo = trim((string) $request->input("motivo.$id", $request->input('motivo_inicio', 'Reprogramación manual')));
        unset($ajustes[$id]);
        unset($motivos[$id]);

        return [$id, Carbon::parse($inicioTexto), $ajustes, $motivos, $motivo ?: 'Reprogramación manual'];
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