<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
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

            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'usuario' => 'Alguna de las credenciales no son correctas',
        ])->onlyInput('usuario');
    }

    /**
     * Cerrar la sesión.
     */
    public function cerrarSesion(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}