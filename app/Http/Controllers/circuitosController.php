<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\circuitosModel;
use App\Models\zonasModel;
use App\Models\PuntoAbastecimiento;

class circuitosController extends Controller
{
    public function index()
    {
        $circuitos = circuitosModel::with('zona', 'puntoAbastecimiento')->get();
        return view('circuitos.index', compact('circuitos'));
    }

    public function create()
    {
        $zonas = zonasModel::all();
        $puntos = PuntoAbastecimiento::where('activo', true)->orderBy('nombre')->get();
        return view('circuitos.create', compact('zonas', 'puntos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'descripcion' => 'nullable|string|max:255',
            'duracion' => 'required|numeric|min:1',
            'punto_abastecimiento_id' => 'nullable|exists:puntos_abastecimiento,id',
            'activo' => 'required|boolean',
        ]);

        $circuito = circuitosModel::create([
            'zona_id' => $request->zona_id,
            'nombre' => circuitosModel::generarCodigo((int) $request->zona_id),
            'descripcion' => $request->descripcion,
            'duracion' => $request->duracion,
            'punto_abastecimiento_id' => $request->punto_abastecimiento_id ?: null,
            'activo' => $request->activo,
        ]);

        app(\App\Services\HistorialActividadService::class)->registrar(
            'circuitos',
            'registro',
            'Se registró un circuito.',
            ['circuito' => $circuito->nombre, 'zona_id' => $circuito->zona_id, 'calles' => $circuito->descripcion]
        );
        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito registrado exitosamente!');
    }

    public function edit($id)
    {
        $circuito = circuitosModel::findOrFail($id);
        $zonas = zonasModel::all();
        $puntos = PuntoAbastecimiento::where('activo', true)->orderBy('nombre')->get();
        return view('circuitos.edit', compact('circuito', 'zonas', 'puntos'));
    }

    public function update(Request $request, $id)
    {
        $circuito = circuitosModel::findOrFail($id);

        $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'descripcion' => 'nullable|string|max:255',
            'duracion' => 'required|integer|min:1',
            'punto_abastecimiento_id' => 'nullable|exists:puntos_abastecimiento,id',
            'activo' => 'required|boolean',
        ]);

        // Si cambia de zona, el código se regenera para que las iniciales coincidan
        $nombre = $circuito->nombre;
        if ((int) $request->zona_id !== (int) $circuito->zona_id) {
            $nombre = circuitosModel::generarCodigo((int) $request->zona_id);
        }

        $antes = $circuito->only(["zona_id", "nombre", "descripcion", "duracion", "punto_abastecimiento_id", "activo"]);
        $circuito->update([
            'zona_id' => $request->zona_id,
            'nombre' => $nombre,
            'descripcion' => $request->descripcion,
            'duracion' => $request->duracion,
            'punto_abastecimiento_id' => $request->punto_abastecimiento_id ?: null,
            'activo' => $request->activo,
        ]);

        app(\App\Services\HistorialActividadService::class)->registrar(
            'circuitos',
            'edicion',
            'Se actualizaron los datos de un circuito.',
            ['circuito' => $circuito->nombre, 'antes' => $antes, 'despues' => $circuito->only(["zona_id", "nombre", "descripcion", "duracion", "punto_abastecimiento_id", "activo"])]
        );
        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito actualizado exitosamente!');
    }

    public function destroy($id)
    {
        $circuito = circuitosModel::findOrFail($id);

        $tandeosAsociados = $circuito->tandeos()
            ->with(['cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            }])
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada')
            ->get();

        if ($tandeosAsociados->count() > 0) {
            // si tiene historial: no se puede borrar, se muestra la lista de asociados
            // y se ofrece la opción de desactivar
            return view('circuitos.tandeosAsociados', compact('circuito', 'tandeosAsociados'));
        }

        // si no hay tandeos asociados: se puede eliminar de forma segura
        $datosCircuito = $circuito->only(["zona_id", "nombre", "descripcion", "duracion"]);
        $circuito->delete();
        app(\App\Services\HistorialActividadService::class)->registrar(
            "circuitos", "eliminacion", "Se eliminó un circuito.", ["circuito_eliminado" => $datosCircuito]
        );

        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito eliminado exitosamente!');
    }

    public function desactivar($id)
    {
        $circuito = circuitosModel::findOrFail($id);
        $circuito->update(['activo' => false]);

        app(\App\Services\HistorialActividadService::class)->registrar(
            'circuitos',
            'edicion',
            'Se desactivó un circuito.',
            ['circuito' => $circuito->nombre, 'activo' => false]
        );

        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito desactivado exitosamente!');
    }

    public function confirmDelete($id)
    {
        $circuito = circuitosModel::findOrFail($id);
        return view('circuitos.delete', compact('circuito'));
    }
}