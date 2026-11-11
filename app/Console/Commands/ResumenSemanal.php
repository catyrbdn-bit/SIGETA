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
        $this->info("Se prepararon {$cantidad} resúmenes semanales.");

        return self::SUCCESS;
    }
}
