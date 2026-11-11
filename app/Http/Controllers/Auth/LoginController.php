<?php

namespace App\Http\Controllers\Auth;

// archivo del sistema: archivo con acciones para las pantallas del sistema

use App\Http\Controllers\Controller;
use App\Services\HistorialActividadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Mostrar el formulario de inicio de sesión.
     */
    // Muestra el formulario de inicio de sesión.
    // descripcion: muestra el formulario de inicio de sesión.
    // entradas: no requiere datos externos
    // salida: muestra la pantalla para ingresar al sistema
    public function mostrarInicioSesion()
    {
        return view('auth.login');
    }

    /**
     * Valida las credenciales y registra el resultado del inicio de sesión.
     *
     * @param  Request  $request  Datos de usuario y contraseña enviados desde el formulario
     * @return RedirectResponse Abre el panel o vuelve al formulario con un aviso
     */
    public function iniciarSesion(Request $request)
    {
        // Validar los datos del formulario
        $credenciales = $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        // Este bloque de código intenta autenticar al usuario con las credenciales proporcionadas
        if (Auth::attempt([
            'usuario' => $credenciales['usuario'],
            'password' => $credenciales['password'],
            'activo' => true,
        ])) {
            // Si la autenticación es exitosa, se regenera la sesión para evitar ataques de fijacion de sesion
            $request->session()->regenerate();

            // Se registra el inicio de sesión exitoso en el historial de actividad
            app(HistorialActividadService::class)->registrar(
                'inicio_sesion', 'exitoso', 'Inicio de sesión exitoso',
                ['usuario' => Auth::user()->usuario]
            );

            return redirect()->intended(route('dashboard'));
        }

        // Si la autenticación falla, se registra el intento fallido en el historial de actividad
        app(HistorialActividadService::class)->registrar(
            'inicio_sesion', 'fallido', 'Intento de inicio de sesión fallido.',
            ['usuario' => (string) $request->input('usuario', '')],
            null, (string) $request->input('usuario', '') ?: 'Usuario desconocido'
        );

        // Se retorna al formulario de inicio de sesión con un mensaje de error
        return back()->withErrors([
            'usuario' => 'Alguna de las credenciales no son correctas',
        ])->onlyInput('usuario');
    }

    // funcion para cerrar sesion
    // Cierra la sesión activa y regresa a la pantalla de inicio.
    // descripcion: cierra la sesión activa y regresa a la pantalla de inicio.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: cierra la sesión y muestra la pantalla de acceso
    public function cerrarSesion(Request $request)
    {
        app(HistorialActividadService::class)->registrar(
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
