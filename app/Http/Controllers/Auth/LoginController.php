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

    /**
     * Procesar el inicio de sesión.
     */
    public function iniciarSesion(Request $request)
    {
        $credenciales = $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt([
            'usuario' => $credenciales['usuario'],
            'password' => $credenciales['password'],
            'activo' => true,
        ])) {
            $request->session()->regenerate();

            app(\App\Services\HistorialActividadService::class)->registrar(
                'inicio_sesion',
                'exitoso',
                'Inicio de sesión exitoso.',
                ['usuario' => Auth::user()->usuario]
            );

            return redirect()->intended(route('dashboard'));
        }

        app(\App\Services\HistorialActividadService::class)->registrar(
            'inicio_sesion',
            'fallido',
            'Intento de inicio de sesión fallido.',
            ['usuario' => (string) $request->input('usuario', '')],
            null,
            (string) $request->input('usuario', '') ?: 'Usuario desconocido'
        );

        return back()->withErrors([
            'usuario' => 'Alguna de las credenciales no son correctas',
        ])->onlyInput('usuario');
    }

    /**
     * Cerrar la sesión.
     */
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
