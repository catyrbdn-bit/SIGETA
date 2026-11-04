<?php
// archivo del sistema: archivo que define las rutas de acceso del sistema

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Define la respuesta que entrega esta ruta al usuario.
// descripcion: define la respuesta que entrega esta ruta al usuario.
// entradas: solicitud que llega a la ruta
// salida: muestra la pantalla o respuesta asignada a la ruta
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('avisos:resumen-semanal')->weeklyOn(5, '08:00')->withoutOverlapping();
Schedule::command('avisos:revisar-pendientes')->everyFiveMinutes()->withoutOverlapping();
