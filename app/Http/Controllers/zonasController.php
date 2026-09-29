<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\zonasModel;

class zonasController extends Controller
{
    public function index()
    {
        $zonas = zonasModel::all();
        return view('zonas.index', compact('zonas'));
    }


    //funcion create para mostrar el formulario de creacion de zona
    public function create()
    {
        return view('zonas.create');
    }


    //funcion store para guardar la zona en la base de datos
   public function store(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:100',
        'descripcion' => 'nullable|string|max:255',
        'activo' => 'required|boolean',
    ]);

    zonasModel::create([
        'nombre' => $request->nombre,
        'descripcion' => $request->descripcion,
        'activo' => $request->activo,
    ]);

    return redirect()->route('zonas.index')
        ->with('success', '¡Zona registrada exitosamente!');
}

    //funcion edit para mostrar el formulario de edicion de zona
    public function edit($id)
    {
        $zona = zonasModel::findOrFail($id);
        return view('zonas.edit', compact('zona'));
    }

    //funcion update para actualizar la zona en la base de datos
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100', // Validación 
            'descripcion' => 'nullable|string|max:255', //puede ser nulo que significa que no es obligatorio
            'activo' => 'required|boolean',
        ]);

        //findOrFail busca la zona por id y si no la encuentra lanza una excepcion
        $zona = zonasModel::findOrFail($id);
        $zona->update($request->all());

        return redirect()->route('zonas.index')
                         ->with('success', 'Zona actualizada exitosamente');
    }


    //funcion destroy para eliminar la zona de la base de datos
    public function destroy($id)
    {
        $zona = zonasModel::findOrFail($id);
        $zona->delete();

        return redirect()->route('zonas.index')
                        ->with('success', 'Zona eliminada exitosamente');

    }

    //alertas para confirmar la eliminacion de la zona
    public function confirmDelete($id)
    {
        $zona = zonasModel::findOrFail($id);
        return view('zonas.delete', compact('zona'));

    }
}
