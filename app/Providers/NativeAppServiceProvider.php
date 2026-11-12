<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Native\Desktop\Facades\Window;
use Native\Desktop\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Prepara los datos iniciales una sola vez y abre la ventana de SIGETA.
     */
    public function boot(): void
    {
        if (config('database.default') === 'nativephp') {
            $archivoMarca = storage_path('app/sigeta-inicializado');

            if (
                ! file_exists($archivoMarca)
                && Schema::hasTable('usuarios')
                && Schema::hasTable('puntos_abastecimiento')
            ) {
                $resultado = Artisan::call('db:seed', [
                    '--class' => \Database\Seeders\DatabaseSeeder::class,
                    '--force' => true,
                ]);

                if ($resultado === 0) {
                    file_put_contents($archivoMarca, 'Datos iniciales cargados');
                }
            }
        }

        Window::open();
    }

    public function phpIni(): array
    {
        return [];
    }
}