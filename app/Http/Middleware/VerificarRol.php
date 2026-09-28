<?php

namespace App\Http\Middleware;

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