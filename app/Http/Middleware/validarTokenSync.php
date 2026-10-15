<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class validarTokenSync
{
    public function handle(Request $request, Closure $next): Response
    {
        $tokenConfigurado = config('sync.token');
        $tokenRecibido = $request->bearerToken();

        if (!$tokenConfigurado || !$tokenRecibido || !hash_equals($tokenConfigurado, $tokenRecibido)) {
            return response()->json([
                'mensaje' => 'No autorizado.',
            ], 401);
        }

        return $next($request);
    }
}