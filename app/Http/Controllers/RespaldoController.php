<?php

namespace App\Http\Controllers;

use App\Models\RespaldoBd;
use App\Services\HistorialActividadService;
use App\Services\RespaldoBaseDatosService;
use Illuminate\Http\Request;
use Throwable;

class RespaldoController extends Controller
{
    public function index()
    {
        $respaldos = RespaldoBd::with('usuario')->latest()->get();
        return view('respaldos.index', compact('respaldos'));
    }

    public function store(Request $request, RespaldoBaseDatosService $servicio, HistorialActividadService $historial)
    {
        try {
            $respaldo = $servicio->crear((int) $request->user()->getKey());
            $historial->registrar('respaldos', 'creacion', 'Se generó un respaldo de la base de datos.',
                ['archivo' => $respaldo->nombre_archivo], null, null, (int) $respaldo->id);

            return redirect()->route('respaldos.index')->with('success', 'El respaldo se generó correctamente.');
        } catch (Throwable $error) {
            $mensaje = $error instanceof \RuntimeException
                ? $error->getMessage()
                : 'No se pudo generar el respaldo. Revisa que la base de datos esté disponible e inténtalo de nuevo.';
            return redirect()->route('respaldos.index')->with('error', $mensaje);
        }
    }

    public function restore(Request $request, RespaldoBd $respaldo, RespaldoBaseDatosService $servicio, HistorialActividadService $historial)
    {
        $request->validate(['confirmar' => ['required', 'accepted']], [
            'confirmar.accepted' => 'Confirma la restauración antes de continuar.',
        ]);

        try {
            $copiaActual = $servicio->crear((int) $request->user()->getKey());
            $archivos = $servicio->restaurar($respaldo, $copiaActual);
            $historial->registrar('respaldos', 'creacion', 'Se generó una copia antes de una restauración.',
                ['archivo' => $copiaActual->nombre_archivo], null, null, (int) $archivos['respaldo_actual']->id);
            $historial->registrar('respaldos', 'restauracion', 'Se restauró la base de datos desde un respaldo.',
                ['archivo' => $archivos['respaldo_restaurado']->nombre_archivo], null, null, (int) $archivos['respaldo_restaurado']->id);

            return redirect()->route('respaldos.index')->with('success', 'La base de datos se restauró correctamente. Se creó una copia de la información anterior.');
        } catch (Throwable $error) {
            $mensaje = $error instanceof \RuntimeException
                ? $error->getMessage()
                : 'No se pudo restaurar el respaldo. La base de datos actual se conservó; inténtalo de nuevo.';
            return redirect()->route('respaldos.index')->with('error', $mensaje);
        }
    }
}
