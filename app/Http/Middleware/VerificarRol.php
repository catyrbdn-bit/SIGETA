<?php

namespace App\Http\Middleware;
// archivo del sistema: archivo que revisa el acceso a las funciones del sistema

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    /**
     * Verificar que el usuario tenga uno de los roles permitidos
     * 0) sin acceso
     * 1) administrador
     * 2) capturista
     * 3) tandeador
     */
    // Comprueba el rol del usuario antes de permitir el acceso.
    // descripcion: comprueba el rol del usuario antes de permitir el acceso.
    // entradas: solicitud con los datos enviados por el usuario; siguiente paso para continuar la solicitud; lista de datos que se deben procesar
    // salida: continúa con la solicitud o devuelve un aviso de acceso denegado
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $usuario = auth()->user();

        if (!in_array($usuario->rol, $roles)) {
            abort(403, 'No tienes permisos para acceder');
        }

        return $next($request);
    }
}