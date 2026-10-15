<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TandeoProgramado;
use App\Models\circuitosModel;
use App\Models\usuarioModel;
use App\Models\zonasModel;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class tandeosController extends Controller
{
    public function index()
    {
        $tandeos = TandeoProgramado::with([
            'circuito.zona',
            'tandeador',
            'cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            },
        ])
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

        $duracionMinutos = (int) round((float) $datos['duracion'] * 60);
        $horaFin = $horaInicio->copy()->addMinutes($duracionMinutos);

        TandeoProgramado::create([
            'circuito_id' => $circuito->id,
            'fecha' => $datos['fecha'],
            'hora_inicio_programada' => $horaInicio->format('H:i'),
            'hora_fin_programada' => $horaFin->format('H:i'),
            'estado' => 'programado',
            'tandeador_id' => $datos['tandeador_id'] ?? null,
        ]);

        return redirect()->route('tandeos.index')
            ->with('success', '¡Tandeo programado exitosamente!');
    }

    public function edit($id)
    {
        $tandeo = TandeoProgramado::findOrFail($id);

        $circuitos = circuitosModel::with('zona')->where('activo', true)->get();

        $tandeadores = usuarioModel::where('rol', 'tandeador')->get();

        return view('tandeos.edit', compact('tandeo', 'circuitos', 'tandeadores'));
    }

    public function destroy($id)
    {
        $tandeo = TandeoProgramado::findOrFail($id);
        $tandeo->delete();

        return redirect()->route('tandeos.index')
            ->with('success', '¡Tandeo eliminado exitosamente!');
    }
}