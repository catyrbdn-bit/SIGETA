<?php

namespace App\Http\Controllers\Auth;
// archivo del sistema: archivo con acciones para las pantallas del sistema

use App\Http\Controllers\controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class passwordResetController extends controller
{
    // muestra el formulario para recuperar la contrasena
    public function solicitar()
    {
        return view('auth.forgot-password');
    }

    // valida el correo y solicita el enlace para cambiar la contrasena
    public function enviarEnlace(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $estado = Password::sendResetLink($request->only('email'));

        if ($estado === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Si el correo pertenece a una cuenta registrada, enviaremos un enlace temporal para cambiar la contraseña.');
        }

        if ($estado === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => 'Espera un momento antes de pedir otro enlace.']);
        }

        return back()->withErrors(['email' => 'No se pudo enviar el enlace. Revisa el correo e inténtalo de nuevo.']);
    }

    // muestra el formulario para crear una contrasena nueva
    public function cambiar($token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    // valida el enlace y guarda la contrasena nueva
    public function guardarNueva(Request $request)
    {
        $datos = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $estado = Password::reset($datos, function ($usuario, $password) {
            $usuario->forceFill([
                'password' => Hash::make($password),
            ])->save();

            event(new PasswordReset($usuario));
        });

        if ($estado === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Contraseña cambiada. Ya puedes iniciar sesión.');
        }

        return back()->withErrors(['email' => 'El enlace ya no es válido o venció. Solicita uno nuevo'])
        ->withInput($request->only('email'));
    }
}
