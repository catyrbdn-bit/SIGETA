<?php

namespace App\Http\Middleware;

use App\Services\AvisosService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class RevisarResumenSemanal
{
    // Revisa al abrir el sistema si falta preparar el resumen de la semana.
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') && $request->hasSession()) {
            $viernes = now()->startOfWeek(Carbon::MONDAY)
                ->addDays(4)
                ->setTime(9, 0);

            if (now()->lt($viernes)) {
                $viernes->subWeek();
            }

            $claveRevision = 'sigeta_resumen_semanal_' . $viernes->format('Ymd');

            if (! $request->session()->has($claveRevision)) {
                app(AvisosService::class)->revisarResumenSemanalAlAbrir();
                $request->session()->put($claveRevision, true);
            }
        }

        return $next($request);
    }
}
