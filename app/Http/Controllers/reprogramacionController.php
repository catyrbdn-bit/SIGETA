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

    // Vista previa: calcula los movimientos pero NO guarda nada
    public function previa(Request $request)
    {
        $datos = $request->validate([
            'desde' => ['nullable', 'date'],
        ]);

        $desde = isset($datos['desde'])
            ? Carbon::parse($datos['desde'])
            : now()->startOfMinute();

        $resultado = $this->servicio->simularApagon($desde);

        return view('reprogramaciones.previa', compact('desde', 'resultado'));
    }

    // Confirmación: aplica los movimientos y guarda el historial
    public function store(Request $request)
    {
        $datos = $request->validate([
            'desde' => ['required', 'date'],
        ]);

        $desde = Carbon::parse($datos['desde']);

        $this->servicio->aplicarApagon($desde, (int) auth()->user()->id);

        return redirect()->route('cumplimiento.index')
            ->with('success', 'Apagón general registrado y tandeos reprogramados.');
    }
}