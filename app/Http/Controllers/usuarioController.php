<?php

namespace App\Http\Controllers;
// archivo del sistema: archivo con acciones para las pantallas del sistema

use App\Models\UsuarioModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    // consulta y muestra los registros de la pantalla
    // Consulta los registros y prepara la pantalla de listado.
    // descripcion: consulta los registros y prepara la pantalla de listado.
    // entradas: no requiere datos externos
    // salida: muestra la pantalla con los datos consultados
    public function index()
    {
        $usuarios = UsuarioModel::orderBy('nombre')->orderBy('apellido_paterno')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    // prepara los datos para mostrar el formulario de registro
    // Prepara el formulario para registrar un nuevo dato.
    // descripcion: prepara el formulario para registrar un nuevo dato.
    // entradas: no requiere datos externos
    // salida: muestra el formulario de registro
    public function create()
    {
        return view('usuarios.create');
    }

    // valida y guarda la informacion recibida
    // Valida y guarda la información recibida desde el formulario.
    // descripcion: valida y guarda la información recibida desde el formulario.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: guarda el registro y muestra un mensaje o los errores encontrados
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
        ], [
            'nombre.required' => 'Escribe el nombre.',
            'apellido_paterno.required' => 'Escribe el apellido paterno.',
            'apellido_materno.required' => 'Escribe el apellido materno.',
            'usuario.required' => 'Escribe el nombre de usuario.',
            'usuario.unique' => 'Ese nombre de usuario ya está registrado.',
            'email.required' => 'Escribe el correo electrónico.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'email.unique' => 'Ese correo electrónico ya está registrado.',
            'password.required' => 'Escribe una contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'rol.required' => 'Selecciona un rol.',
            'rol.in' => 'Selecciona un rol válido.',
        ]);

        $datos['password'] = Hash::make($datos['password']);
        $datos['activo'] = true;
        $usuario = UsuarioModel::create($datos);

        app(\App\Services\HistorialActividadService::class)->registrar(
            'usuarios',
            'registro',
            'Se registró un usuario.',
            ['usuario_registrado' => $usuario->usuario, 'rol' => $usuario->rol]
        );

        return redirect()->route('usuarios.index')->with('success', 'Usuario registrado correctamente.');
    }

    // busca el registro y prepara el formulario de edicion
    // Busca el registro y prepara el formulario para modificarlo.
    // descripcion: busca el registro y prepara el formulario para modificarlo.
    // entradas: identificador del registro relacionado
    // salida: muestra el formulario con los datos actuales
    public function edit($id)
    {
        $usuario = UsuarioModel::findOrFail($id);

        return view('usuarios.edit', compact('usuario'));
    }

    // valida y guarda los cambios del registro
    // Valida y guarda los cambios del registro.
    // descripcion: valida y guarda los cambios del registro.
    // entradas: solicitud con los datos enviados por el usuario; identificador del registro relacionado
    // salida: actualiza el registro y muestra un mensaje o los errores encontrados
    public function update(Request $request, $id)
    {
        $usuario = UsuarioModel::findOrFail($id);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:50'],
            'apellido_materno' => ['required', 'string', 'max:50'],
            'usuario' => ['required', 'string', 'max:50', Rule::unique('usuarios', 'usuario')->ignore($usuario->id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($usuario->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'rol' => ['required', Rule::in(['administrador', 'tandeador', 'capturista'])],
            'activo' => ['required', 'boolean'],
        ], [
            'nombre.required' => 'Escribe el nombre.',
            'apellido_paterno.required' => 'Escribe el apellido paterno.',
            'apellido_materno.required' => 'Escribe el apellido materno.',
            'usuario.required' => 'Escribe el nombre de usuario.',
            'usuario.unique' => 'Ese nombre de usuario ya está registrado.',
            'email.required' => 'Escribe el correo electrónico.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'email.unique' => 'Ese correo electrónico ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'rol.required' => 'Selecciona un rol.',
            'rol.in' => 'Selecciona un rol válido.',
            'activo.required' => 'Selecciona el estado del usuario.',
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

    // elimina el registro seleccionado
    // Elimina el registro seleccionado y atiende sus datos relacionados.
    // descripcion: elimina el registro seleccionado y atiende sus datos relacionados.
    // entradas: identificador del registro relacionado
    // salida: elimina el registro y regresa al listado con un mensaje
    public function destroy($id)
    {
        $usuario = UsuarioModel::findOrFail($id);
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
