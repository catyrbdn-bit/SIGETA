<?php

namespace App\Http\Controllers\Auth;

// archivo del sistema: archivo con acciones para las pantallas del sistema

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    // muestra el formulario para recuperar la contrasena
    // Muestra el formulario para solicitar la recuperación de contraseña.
    // descripcion: muestra el formulario para solicitar la recuperación de contraseña.
    // entradas: no requiere datos externos
    // salida: muestra la pantalla de recuperación
    public function solicitar()
    {
        return view('auth.forgot-password');
    }

    // valida el correo y solicita el enlace para cambiar la contrasena
    // Valida la cuenta y envía el enlace de recuperación de contraseña.
    // descripcion: valida la cuenta y envía el enlace de recuperación de contraseña.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: envía el enlace o muestra un mensaje sobre el resultado
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
    // Revisa el enlace recibido y muestra el formulario para cambiar la contraseña.
    // descripcion: revisa el enlace recibido y muestra el formulario para cambiar la contraseña.
    // entradas: dato necesario para realizar el proceso
    // salida: muestra el formulario o informa que el enlace no es válido
    public function cambiar($token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    // valida el enlace y guarda la contrasena nueva
    // Valida el enlace y guarda la contraseña nueva del usuario.
    // descripcion: valida el enlace y guarda la contraseña nueva del usuario.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: guarda la contraseña y muestra un mensaje sobre el resultado
    public function guardarNueva(Request $request)
    {
        $datos = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Guarda la contraseña nueva y actualiza la cuenta del usuario.
        // descripcion: guarda la contraseña nueva y actualiza la cuenta del usuario.
        // entradas: usuario y contraseña nueva
        // salida: guarda la contraseña cifrada y actualiza al usuario
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
