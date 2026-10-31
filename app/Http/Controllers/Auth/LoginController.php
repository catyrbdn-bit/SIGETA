<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class loginController extends controller
{
    /**
     * Mostrar el formulario de inicio de sesión.
     */
    public function mostrarInicioSesion()
    {
        return view('auth.login');
    }

  
    //Funcion para procesar el inicio de sesion
    //Se ingresan los datos del formulario recibidos en $request, 
    // y se valida si son correctos, si no se regresa al 
    // formulario con un mensaje de error

    public function iniciarSesion(Request $request)
    {
        // Validar los datos del formulario
        $credenciales = $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        //Este bloque de código intenta autenticar al usuario con las credenciales proporcionadas
        if (Auth::attempt([
            'usuario' => $credenciales['usuario'],
            'password' => $credenciales['password'],
            'activo' => true,
        ])) {
            // Si la autenticación es exitosa, se regenera la sesión para evitar ataques de fijacion de sesion
            $request->session()->regenerate();

            // Se registra el inicio de sesión exitoso en el historial de actividad
            app(\App\Services\HistorialActividadService::class)->registrar(
                'inicio_sesion', 'exitoso', 'Inicio de sesión exitoso',
                ['usuario' => Auth::user()->usuario]
            );

            return redirect()->intended(route('dashboard'));
        }

        // Si la autenticación falla, se registra el intento fallido en el historial de actividad
        app(\App\Services\HistorialActividadService::class)->registrar(
            'inicio_sesion','fallido','Intento de inicio de sesión fallido.',
            ['usuario' => (string) $request->input('usuario', '')],
            null, (string) $request->input('usuario', '') ?: 'Usuario desconocido'
        );

        // Se retorna al formulario de inicio de sesión con un mensaje de error
        return back()->withErrors([
            'usuario' => 'Alguna de las credenciales no son correctas',
        ])->onlyInput('usuario');
    }

     //funcion para cerrar sesion
    public function cerrarSesion(Request $request)
    {
        app(\App\Services\HistorialActividadService::class)->registrar(
            'sesion',
            'cierre',
            'El usuario cerró sesión.'
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
