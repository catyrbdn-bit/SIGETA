<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\zonasController;

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
    return view('dashboard');
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


    