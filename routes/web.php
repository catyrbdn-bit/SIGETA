<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\zonasController;

// ==================================================
// ZONAS
// ==================================================
// Pagina principal: modulo de zonas

Route::get('/', [zonasController::class, 'index'])
    ->name('inicio');

// Mostrar panel de zonas
Route::get('/zonas', [zonasController::class, 'index'])
    ->name('zonas.index');
  
route::get('/zonas/home', [zonasController::class, 'index'])
    ->name('zonasHome');

Route::get('/zonas/crear', [zonasController::class, 'create'])
    ->name('createZona');

Route::post('/zonas/guardar', [zonasController::class, 'store'])
    ->name('storeZona');



// ==================================================
// INICIO DE SESIÓN
// ==================================================

Route::get('/login', [LoginController::class, 'mostrarInicioSesion'])
    ->name('login');

Route::post('/login', [LoginController::class, 'iniciarSesion'])
    ->name('iniciarSesion');

// Cerrar sesion
Route::post('/logout', [LoginController::class, 'cerrarSesion'])
    ->name('cerrarSesion');

// Panel principal
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');