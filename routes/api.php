<?php

use App\Http\Controllers\syncController;
use Illuminate\Support\Facades\Route;

Route::middleware('sync.token')->prefix('sync')->group(function () {
    Route::get('/estado', function () {
        return response()->json([
            'estado' => 'conectada',
            'mensaje' => 'La API de SIGETA está disponible.',
        ]);
    });

    Route::post('/subir', [syncController::class, 'subir']);
    Route::get('/descargar', [syncController::class, 'descargar']);
});