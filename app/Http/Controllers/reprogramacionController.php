<?php

namespace App\Http\Controllers;

use App\Models\PuntoAbastecimiento;
use App\Services\reprogramacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class reprogramacionController extends Controller
{
    public function __construct(private reprogramacionService $servicio)
    {
    }

    // Formulario del apagón general
    public function create()
    {
        $puntos = PuntoAbastecimiento::where('activo', true)->orderBy('nombre')->get();

        return view('reprogramaciones.apagon', compact('puntos'));
    }

    // Vista previa: calcula los movimientos pero NO guarda nada
    public function previa(Request $request)
    {
        $datos = $this->validar($request);

        $punto = PuntoAbastecimiento::findOrFail($datos['punto_abastecimiento_id']);
        $desde = Carbon::parse($datos['desde']);

        $resultado = $this->servicio->simularApagon($punto, $desde, $datos['horas'] ?? null);

        return view('reprogramaciones.previa', compact('punto', 'desde', 'resultado'));
    }

    // Confirmación: aplica los movimientos y guarda el historial
    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $punto = PuntoAbastecimiento::findOrFail($datos['punto_abastecimiento_id']);
        $desde = Carbon::parse($datos['desde']);

        $this->servicio->aplicarApagon($punto, $desde, $datos['horas'] ?? null, auth()->id());

        return redirect()
            ->route('reprogramaciones.apagon.create')
            ->with('success', 'Apagón registrado y tandeos reprogramados.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'punto_abastecimiento_id' => ['required', 'exists:puntos_abastecimiento,id'],
            'desde'                   => ['required', 'date'],
            'horas'                   => ['nullable', 'numeric', 'min:0.5', 'max:24'],
        ]);
    }
}