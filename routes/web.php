<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\loginController;
use App\Http\Controllers\Auth\passwordResetController;
use App\Http\Controllers\usuarioController;
use App\Http\Controllers\zonasController;
use App\Http\Controllers\circuitosController;
use App\Http\Controllers\tandeosController;
use App\Http\Controllers\cumplimientoController;
use App\Http\Controllers\reprogramacionController;
use App\Http\Controllers\calendarioController;
use App\Http\Controllers\historialActividadController;
use App\Http\Controllers\reportesController;


// ==================================================
// INICIO DE SESIÓN
// ==================================================

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [loginController::class, 'mostrarInicioSesion'])
        ->name('login');

    Route::post('/login', [loginController::class, 'iniciarSesion'])
        ->name('iniciarSesion');

    Route::get('/forgot-password', [passwordResetController::class, 'solicitar'])
        ->name('password.request');

    Route::post('/forgot-password', [passwordResetController::class, 'enviarEnlace'])
        ->name('password.email');

    Route::get('/reset-password/{token}', [passwordResetController::class, 'cambiar'])
        ->name('password.reset');

    Route::post('/reset-password', [passwordResetController::class, 'guardarNueva'])
        ->name('password.update');
});

Route::middleware('auth')->group(function () {

Route::post('/logout', [loginController::class, 'cerrarSesion'])
    ->name('cerrarSesion');

// Panel principal

Route::get('/dashboard', function () {
    app(\App\Services\repeticionTandeoService::class)->crearSiguientes();

    $mesNumero = request('mes_num');
    $anioSeleccionado = request('anio');

    //su funcion de este if es validar que el año y el mes sean correctos, 
    // si no lo son, se asigna el mes actual
    if (preg_match('/^\d{4}$/', (string) $anioSeleccionado)
        && preg_match('/^(0?[1-9]|1[0-2])$/', (string) $mesNumero)) {
        $mesActual = sprintf('%04d-%02d', (int) $anioSeleccionado, (int) $mesNumero);
    } else {
        $mesActual = request('mes', now()->format('Y-m'));
    }

    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mesActual)) {
        $mesActual = now()->format('Y-m');
    }

    $mesNumero = (int) substr($mesActual, 5, 2);
    $anioSeleccionado = (int) substr($mesActual, 0, 4);
    $fechaMes = \Carbon\Carbon::createFromFormat('!Y-m', $mesActual);
    $inicioMes = $fechaMes->copy()->startOfMonth()->toDateString();
    $finMes = $fechaMes->copy()->endOfMonth()->toDateString();

    $busqueda = trim((string) request('buscar', ''));

    $tandeosMes = \App\Models\TandeoProgramado::query()
        ->when($busqueda !== '', function ($consulta) use ($busqueda) {
            $consulta->whereHas('circuito', function ($circuito) use ($busqueda) {
                $circuito->where('nombre', 'like', '%' . $busqueda . '%')
                    ->orWhere('descripcion', 'like', '%' . $busqueda . '%')
                    ->orWhereHas('zona', function ($zona) use ($busqueda) {
                        $zona->where('nombre', 'like', '%' . $busqueda . '%');
                    });
            });
        })
        ->when($busqueda === '', function ($consulta) use ($inicioMes, $finMes) {
            $consulta->whereBetween('fecha', [$inicioMes, $finMes]);
        })
        ->when(auth()->user()->rol === 'tandeador', function ($consulta) {
            $consulta->where(function ($filtro) {
                $filtro->where('tandeador_id', auth()->user()->getKey())
                    ->orWhereHas('tandeadores', function ($personas) {
                        $personas->where('usuarios.id', auth()->user()->getKey());
                    });
            });
        })
        ->with([
            'tandeadores',
            'circuito.zona',
            'circuito.puntoAbastecimiento',
            'cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            },
        ])
        ->orderBy('fecha')
        ->orderBy('hora_inicio_programada')
        ->get();

    return view('dashboard', compact('tandeosMes', 'mesActual', 'mesNumero', 'anioSeleccionado', 'fechaMes', 'busqueda'));
})->name('dashboard');

Route::get('/calendario', [calendarioController::class, 'index'])
    ->middleware('rol:administrador,capturista,tandeador')
    ->name('calendario.index');



Route::get('/calendario/eventos', [calendarioController::class, 'eventos'])
    ->middleware('rol:administrador,capturista,tandeador')
    ->name('calendario.eventos');

Route::post('/calendario/mover', [calendarioController::class, 'mover'])
    ->middleware('rol:administrador,capturista')
    ->name('calendario.mover');
Route::middleware('rol:administrador')->prefix('usuarios')
->name('usuarios.')->group(function () {
    Route::get('/', [usuarioController::class, 'index'])
    ->name('index');
    Route::get('/crear', [usuarioController::class, 'create'])
    ->name('create');
    Route::post('/', [usuarioController::class, 'store'])
    ->name('store');
    Route::get('/{id}/editar', [usuarioController::class, 'edit'])
    ->name('edit');
    Route::put('/{id}', [usuarioController::class, 'update'])
    ->name('update');
    Route::delete('/{id}', [usuarioController::class, 'destroy'])
    ->name('destroy');
});


Route::middleware('rol:administrador')->prefix('historial')->name('historial.')->group(function () {
    Route::get('/', [historialActividadController::class, 'index'])->name('index');
    Route::post('/depurar', [historialActividadController::class, 'depurar'])->name('depurar');
});
// ==================================================

// REPORTES
Route::middleware('rol:administrador,capturista')->prefix('reportes')->name('reportes.')->group(function () {
    Route::get('/', [reportesController::class, 'index'])->name('index');
    Route::get('/cumplimiento-circuito/excel', [reportesController::class, 'cumplimientoCircuitoExcel'])->name('cumplimiento.circuito.excel');
    Route::get('/{tipo}/pdf', [reportesController::class, 'generarPdf'])->name('pdf');
});

// ZONAS
// ==================================================

Route::middleware('rol:administrador,capturista')->group(function () {
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
    Route::delete('/zonas/{id}', [zonasController::class, 'destroy'])->name('deleteZona');
});


// ==================================================
// CIRCUITOS
// ==================================================

Route::middleware('rol:administrador,capturista')->group(function () {
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
});


// ==================================================
// TANDEOS PROGRAMADOS
// ==================================================

Route::get('/tandeos', [tandeosController::class, 'index'])
    ->name('tandeos.index');

Route::middleware('rol:administrador,capturista')->group(function () {
    Route::get('/tandeos/crear', [tandeosController::class, 'create'])
    ->name('createTandeo');
    Route::post('/tandeos/guardar', [tandeosController::class, 'store'])
    ->name('storeTandeo');
    Route::get('/tandeos/{id}/editar', [tandeosController::class, 'edit'])
    ->name('editTandeo');
    Route::delete('/tandeos/{id}', [tandeosController::class, 'destroy'])->name('deleteTandeo');
});
    

// ==================================================
// CUMPLIMIENTO DE TANDEOS
// ==================================================

Route::middleware('rol:administrador,capturista')->group(function () {
    Route::get('/cumplimiento', [cumplimientoController::class, 'index'])
    ->name('cumplimiento.index');
    Route::post('/cumplimiento/guardar', [cumplimientoController::class, 'store'])
    ->name('storeCumplimiento');
    Route::post('/cumplimiento/apagon-general', [cumplimientoController::class, 'apagonGeneral'])
    ->name('apagonGeneral');
    Route::post('/cumplimiento/fuga', [cumplimientoController::class, 'fuga'])
    ->name('registrarFuga');
});

    
// ==================================================
// REPROGRAMACIONES
// ==================================================

Route::middleware('rol:administrador,capturista')->group(function () {
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

});
});

