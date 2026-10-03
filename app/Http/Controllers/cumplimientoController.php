<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CumplimientoTandeo;
use App\Models\tandeoProgramado;
use App\Models\zonasModel;

class cumplimientoController extends Controller
{
    public function index()
    {
        $inicioSemana = now()->startOfWeek()->toDateString();
        $finSemana = now()->endOfWeek()->toDateString();

        $tandeos = tandeoProgramado::whereDate('fecha', '>=', $inicioSemana)
            ->whereDate('fecha', '<=', $finSemana)
            ->with('circuito.zona')
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada')
            ->get();

        $zonas = zonasModel::where('activo', true)->get();

        return view('cumplimiento.index', compact('tandeos', 'zonas'));
    }

    // Registro individual de cumplimiento de un tandeo
    public function store(Request $request)
    {
        $request->validate([
            'tandeo_id' => 'required|exists:tandeos_programados,id',
            'resultado' => 'required|in:cumplido,atraso,no_cumplido',
            'horas_atraso' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string',
        ]);

        CumplimientoTandeo::create([
            'tandeo_id' => $request->tandeo_id,
            'resultado' => $request->resultado,
            'horas_atraso' => $request->horas_atraso,
            'observaciones' => $request->observaciones,
            'capturado_por_id' => auth()->user()->id,
            'fecha_captura' => now(),
        ]);

        return redirect()->route('cumplimiento.index')
            ->with('success', '¡Cumplimiento registrado exitosamente!');
    }

    // Botón "Apagón general": afecta los tandeos de hoy en todas las zonas
    public function apagonGeneral(Request $request)
    {
        $request->validate([
            'horas_atraso' => 'required|integer|min:1',
        ]);

        $tandeosHoy = tandeoProgramado::whereDate('fecha', now())
            ->where('estado', 'programado')
            ->get();

        foreach ($tandeosHoy as $tandeo) {
            CumplimientoTandeo::create([
                'tandeo_id' => $tandeo->id,
                'resultado' => 'atraso',
                'horas_atraso' => $request->horas_atraso,
                'motivo' => 'apagon_general',
                'capturado_por_id' => auth()->user()->id,
                'fecha_captura' => now(),
            ]);

            // TODO (FN.8): el motor de reprogramaciones debe recorrer
            // hora_inicio_programada / hora_fin_programada / fecha de
            // $tandeo según $request->horas_atraso, y registrar el
            // movimiento en la tabla de reprogramaciones.
        }

        return redirect()->route('cumplimiento.index')
            ->with('success', 'Apagón general registrado, se aplicó a todas las zonas.');
    }

    // Botón "Fuga": afecta los tandeos de hoy solo en la zona indicada
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
            ->get();

        foreach ($tandeosZona as $tandeo) {
            CumplimientoTandeo::create([
                'tandeo_id' => $tandeo->id,
                'resultado' => 'atraso',
                'horas_atraso' => $request->horas_atraso,
                'motivo' => 'fuga',
                'capturado_por_id' => auth()->user()->id,
                'fecha_captura' => now(),
            ]);

            // TODO (FN.8): motor de reprogramaciones para esta zona.
        }

        return redirect()->route('cumplimiento.index')
            ->with('success', 'Fuga registrada, se aplicó a la zona seleccionada.');
    }
}