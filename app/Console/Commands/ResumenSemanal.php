<?php

namespace App\Console\Commands;

use App\Services\AvisosService;
use Illuminate\Console\Command;

class ResumenSemanal extends Command
{
    protected $signature = 'avisos:resumen-semanal';

    protected $description = 'Prepara el resumen semanal de tandeos para los viernes.';

    public function handle(AvisosService $avisos): int
    {
        $cantidad = $avisos->crearResumenSemanal();
        $pendientes = $avisos->revisarAvisosPendientes();
        $this->info("Se agregaron {$cantidad} resúmenes semanales a la cola de correo.");
        $this->line("Se reactivaron {$pendientes} avisos pendientes.");
        $this->line("Para enviarlos desde esta terminal, ejecuta: php artisan queue:work --stop-when-empty");

        return self::SUCCESS;
    }
}
