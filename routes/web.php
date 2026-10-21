<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\zonasController;
use App\Http\Controllers\circuitosController;
use App\Http\Controllers\tandeosController;
use App\Http\Controllers\cumplimientoController;
use App\Http\Controllers\ReprogramacionController;


// ==================================================
// INICIO DE SESIÓN
// ==================================================

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'mostrarInicioSesion'])
    ->name('login');

Route::post('/login', [LoginController::class, 'iniciarSesion'])
    ->name('iniciarSesion');

Route::post('/logout', [LoginController::class, 'cerrarSesion'])
    ->name('cerrarSesion');

// Panel principal

Route::get('/dashboard', function () {
    app(\App\Services\repeticionTandeoService::class)->crearSiguientes();

    $inicioSemana = now()->startOfWeek()->toDateString();
    $finSemana = now()->endOfWeek()->toDateString();

    $tandeosSemana = \App\Models\TandeoProgramado::whereBetween('fecha', [$inicioSemana, $finSemana])
        ->with([
            'circuito.zona',
            'circuito.puntoAbastecimiento',
            'cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            },
        ])
        ->orderBy('fecha')
        ->orderBy('hora_inicio_programada')
        ->get();

    return view('dashboard', compact('tandeosSemana'));
})->middleware('auth')->name('dashboard');


// ==================================================
// ZONAS
// ==================================================

Route::get('/zonas', [zonasController::class, 'index'])
    ->name('zonas.index');

Route::get('/zonas/crear', [zonasController::class, 'create'])
    ->name('createZona');

Route::post('/zonas/guardar', [zonasController::class, 'store'])
    ->name('storeZona');

Route::put('/zonas/{zona}', [zonasController::class, 'update'])
    ->name('updateZona');

Route::get('/zonas/{id}/editar', [zonasController::class, 'edit'])
    ->name('editZona');

Route::delete('/zonas/{id}', [zonasController::class, 'destroy'])
    ->name('deleteZona');


// ==================================================
// CIRCUITOS
// ==================================================


Route::get('/circuitos', [circuitosController::class, 'index'])
    ->name('circuitos.index');

Route::get('/circuitos/crear', [circuitosController::class, 'create'])
    ->name('createCircuito');

Route::post('/circuitos/guardar', [circuitosController::class, 'store'])
    ->name('storeCircuito');

Route::put('/circuitos/{circuito}', [circuitosController::class, 'update'])
    ->name('updateCircuito');

Route::get('/circuitos/{id}/editar', [circuitosController::class, 'edit'])
    ->name('editCircuito');

Route::delete('/circuitos/{id}', [circuitosController::class, 'destroy'])
    ->name('deleteCircuito');

Route::put('/circuitos/{id}/desactivar', [circuitosController::class, 'desactivar'])
    ->name('desactivarCircuito');



// ==================================================
// TANDEOS PROGRAMADOS
// ==================================================

Route::get('/tandeos', [tandeosController::class, 'index'])
    ->name('tandeos.index');

Route::get('/tandeos/crear', [tandeosController::class, 'create'])
    ->name('createTandeo');

Route::post('/tandeos/guardar', [tandeosController::class, 'store'])
    ->name('storeTandeo');

Route::get('/tandeos/{id}/editar', [tandeosController::class, 'edit'])
    ->name('editTandeo');


Route::delete('/tandeos/{id}', [tandeosController::class, 'destroy'])
    ->name('deleteTandeo');
    

// ==================================================
// CUMPLIMIENTO DE TANDEOS
// ==================================================

Route::get('/cumplimiento', [cumplimientoController::class, 'index'])
    ->name('cumplimiento.index');

Route::post('/cumplimiento/guardar', [cumplimientoController::class, 'store'])
    ->name('storeCumplimiento');

Route::post('/cumplimiento/apagon-general', [cumplimientoController::class, 'apagonGeneral'])
    ->name('apagonGeneral');

Route::post('/cumplimiento/fuga', [cumplimientoController::class, 'fuga'])
    ->name('registrarFuga');

    
// ==================================================
// REPROGRAMACIONES
// ==================================================

Route::post('/reprogramaciones/apagon/previa', [reprogramacionController::class, 'previa'])
    ->name('reprogramaciones.apagon.previa');

Route::post('/reprogramaciones/apagon', [reprogramacionController::class, 'store'])
    ->name('reprogramaciones.apagon.store');

Route::post('/reprogramaciones/fuga/previa', [reprogramacionController::class, 'previaFuga'])
    ->name('reprogramaciones.fuga.previa');

Route::post('/reprogramaciones/fuga', [reprogramacionController::class, 'storeFuga'])
    ->name('reprogramaciones.fuga.store');


Route::post('/reprogramaciones/manual/previa', [reprogramacionController::class, 'previaManual'])
    ->name('reprogramaciones.manual.previa');

Route::post('/reprogramaciones/manual/guardar', [reprogramacionController::class, 'storeManual'])
    ->name('reprogramaciones.manual.store');
