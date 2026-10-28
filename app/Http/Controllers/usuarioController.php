<?php

namespace App\Http\Controllers;

use App\Models\usuarioModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class usuarioController extends controller
{
    public function index()
    {
        $usuarios = usuarioModel::orderBy('nombre')->orderBy('apellido_paterno')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:50'],
            'apellido_materno' => ['required', 'string', 'max:50'],
            'usuario' => ['required', 'string', 'max:50', 'unique:usuarios,usuario'],
            'email' => ['required', 'email', 'max:150', 'unique:usuarios,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'rol' => ['required', Rule::in(['administrador', 'tandeador', 'capturista'])],
        ]);

        $datos['password'] = Hash::make($datos['password']);
        $datos['activo'] = true;
        $usuario = usuarioModel::create($datos);

        app(\App\Services\HistorialActividadService::class)->registrar(
            'usuarios',
            'registro',
            'Se registró un usuario.',
            ['usuario_registrado' => $usuario->usuario, 'rol' => $usuario->rol]
        );

        return redirect()->route('usuarios.index')->with('success', 'Usuario registrado correctamente.');
    }

    public function edit($id)
    {
        $usuario = usuarioModel::findOrFail($id);

        return view('usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, $id)
    {
        $usuario = usuarioModel::findOrFail($id);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:50'],
            'apellido_materno' => ['required', 'string', 'max:50'],
            'usuario' => ['required', 'string', 'max:50', Rule::unique('usuarios', 'usuario')->ignore($usuario->id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($usuario->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'rol' => ['required', Rule::in(['administrador', 'tandeador', 'capturista'])],
            'activo' => ['required', 'boolean'],
        ]);

        if (!empty($datos['password'])) {
            $datos['password'] = Hash::make($datos['password']);
        } else {
            unset($datos['password']);
        }

        $antes = $usuario->only(['nombre', 'apellido_paterno', 'apellido_materno', 'usuario', 'rol', 'activo']);
        $usuario->update($datos);

        app(\App\Services\HistorialActividadService::class)->registrar(
            'usuarios',
            'edicion',
            'Se actualizaron los datos de un usuario.',
            [
                'usuario_editado' => $usuario->usuario,
                'antes' => $antes,
                'despues' => $usuario->only(['nombre', 'apellido_paterno', 'apellido_materno', 'usuario', 'rol', 'activo']),
            ]
        );

        return redirect()->route('usuarios.index')->with('success', 'Datos del usuario actualizados.');
    }

    public function destroy($id)
    {
        $usuario = usuarioModel::findOrFail($id);
        $datosUsuario = $usuario->only(['nombre', 'apellido_paterno', 'apellido_materno', 'usuario', 'rol']);
        $usuario->delete();

        app(\App\Services\HistorialActividadService::class)->registrar(
            'usuarios',
            'eliminacion',
            'Se eliminó un usuario.',
            ['usuario_eliminado' => $datosUsuario]
        );

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado. Su historial se conservó.');
    }
}
