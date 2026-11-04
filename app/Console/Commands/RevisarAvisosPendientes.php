<?php

namespace App\Console\Commands;

use App\Services\AvisosService;
use Illuminate\Console\Command;

class RevisarAvisosPendientes extends Command
{
    protected $signature = 'avisos:revisar-pendientes';
    protected $description = 'Vuelve a poner en cola los correos pendientes.';

    public function handle(AvisosService $avisos): int
    {
        $cantidad = $avisos->revisarAvisosPendientes();
        $this->info("Se revisaron {$cantidad} notificaciones pendientes.");
        return self::SUCCESS;
    }
}
