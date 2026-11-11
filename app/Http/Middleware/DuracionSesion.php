<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class DuracionSesion
{
    // Mantiene el cierre por inactividad para usuarios con sesión iniciada
    // descripcion: deja diez minutos a usuarios autenticados y más tiempo a visitantes
    // entradas: solicitud de la página
    // salida: continúa la solicitud con la duración de sesión correspondiente
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if (Auth::check()) {
            Config::set('session.lifetime', 10);
        } else {
            Config::set('session.lifetime', 1440);
        }

        return $respuesta;
    }
}
