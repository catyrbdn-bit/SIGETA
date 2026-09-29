<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\circuitosModel;
use App\Models\zonasModel;

class circuitosController extends Controller
{
    public function index()
    {
        $circuitos = circuitosModel::all();
        return view('circuitos.index', compact('circuitos'));
    }

    public function create()
    {
        $zonas = zonasModel::all();
        return view('circuitos.create', compact('zonas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('circuitos')->where('zona_id', $request->zona_id),
            ],
            'duracion' => 'required|integer|min:1',
            'activo' => 'required|boolean',
        ], [
            'nombre.unique' => 'Ya existe un circuito con ese nombre en esta zona.',
        ]);

        circuitosModel::create([
            'zona_id' => $request->zona_id,
            'nombre' => $request->nombre,
            'duracion' => $request->duracion,
            'activo' => $request->activo,
        ]);

        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito registrado exitosamente!');
    }

    public function edit($id)
    {
        $circuito = circuitosModel::findOrFail($id);
        $zonas = zonasModel::all();
        return view('circuitos.edit', compact('circuito', 'zonas'));
    }

    public function update(Request $request, $id)
    {
        $circuito = circuitosModel::findOrFail($id);

        $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('circuitos')->where('zona_id', $request->zona_id)->ignore($circuito->id),
            ],
            'duracion' => 'required|integer|min:1',
            'activo' => 'required|boolean',
        ], [
            'nombre.unique' => 'Ya existe un circuito con ese nombre en esta zona.',
        ]);

        $circuito->update([
            'zona_id' => $request->zona_id,
            'nombre' => $request->nombre,
            'duracion' => $request->duracion,
            'activo' => $request->activo,
        ]);

        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito actualizado exitosamente!');
    }

    public function destroy($id)
    {
        $circuito = circuitosModel::findOrFail($id);

        // Eliminación lógica: se desactiva en vez de borrarse, para
        // conservar el historial de tandeos asociados (FN.5)
        $circuito->update(['activo' => false]);

        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito desactivado exitosamente!');
    }

    public function confirmDelete($id)
    {
        $circuito = circuitosModel::findOrFail($id);
        return view('circuitos.delete', compact('circuito'));
    }
}