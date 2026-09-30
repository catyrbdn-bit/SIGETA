<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TandeoProgramado;
use App\Models\circuitosModel;
use App\Models\usuarioModel;
use Carbon\Carbon;

class tandeosController extends Controller
{
    public function index()
    {
        $tandeos = TandeoProgramado::with('circuito.zona', 'tandeador')->get();
        return view('tandeos.index', compact('tandeos'));
    }

    public function create()
    {
        $circuitos = circuitosModel::where('activo', true)->get();
        $tandeadores = usuarioModel::where('rol', 'tandeador')->get();
        return view('tandeos.create', compact('circuitos', 'tandeadores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'circuito_id' => 'required|exists:circuitos,id',
            'fecha' => 'required|date',
            'hora_inicio_programada' => 'required',
            'tandeador_id' => 'nullable|exists:usuarios,id',
        ]);

        $circuito = circuitosModel::findOrFail($request->circuito_id);

        $horaInicio = Carbon::parse($request->hora_inicio_programada);
        $horaFin = $horaInicio->copy()->addHours($circuito->duracion);

        TandeoProgramado::create([
            'circuito_id' => $request->circuito_id,
            'fecha' => $request->fecha,
            'hora_inicio_programada' => $horaInicio->format('H:i'),
            'hora_fin_programada' => $horaFin->format('H:i'),
            'estado' => 'programado',
            'tandeador_id' => $request->tandeador_id,
        ]);

        return redirect()->route('tandeos.index')
            ->with('success', '¡Tandeo programado exitosamente!');
    }

    public function edit($id)
    {
        $tandeo = TandeoProgramado::findOrFail($id);
        $circuitos = circuitosModel::where('activo', true)->get();
        $tandeadores = usuarioModel::where('rol', 'tandeador')->get();
        return view('tandeos.edit', compact('tandeo', 'circuitos', 'tandeadores'));
    }

    public function update(Request $request, $id)
    {
        $tandeo = TandeoProgramado::findOrFail($id);

        $request->validate([
            'circuito_id' => 'required|exists:circuitos,id',
            'fecha' => 'required|date',
            'hora_inicio_programada' => 'required',
            'tandeador_id' => 'nullable|exists:usuarios,id',
        ]);

        $circuito = circuitosModel::findOrFail($request->circuito_id);

        $horaInicio = Carbon::parse($request->hora_inicio_programada);
        $horaFin = $horaInicio->copy()->addHours($circuito->duracion);

        $tandeo->update([
            'circuito_id' => $request->circuito_id,
            'fecha' => $request->fecha,
            'hora_inicio_programada' => $horaInicio->format('H:i'),
            'hora_fin_programada' => $horaFin->format('H:i'),
            'tandeador_id' => $request->tandeador_id,
        ]);

        return redirect()->route('tandeos.index')
            ->with('success', '¡Tandeo actualizado exitosamente!');
    }

    public function destroy($id)
    {
        $tandeo = TandeoProgramado::findOrFail($id);
        $tandeo->delete();

        return redirect()->route('tandeos.index')
            ->with('success', '¡Tandeo eliminado exitosamente!');
    }
}