<?php

namespace App\Providers;
// archivo del sistema: archivo que configura servicios de la aplicacion

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    // Registra los servicios que usa la aplicación.
    // descripcion: registra los servicios que usa la aplicación.
    // entradas: no requiere datos externos
    // salida: deja disponibles los servicios para el resto del sistema
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    // Configura los servicios de la aplicación al iniciar.
    // descripcion: configura los servicios de la aplicación al iniciar.
    // entradas: no requiere datos externos
    // salida: deja registrados los servicios que usa la aplicación
    public function boot(): void
    {
        //
    }
}
