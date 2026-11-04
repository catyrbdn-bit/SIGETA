<?php
// archivo del sistema: archivo que define las rutas de acceso del sistema

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ZonasController;
use App\Http\Controllers\CircuitosController;
use App\Http\Controllers\TandeosController;
use App\Http\Controllers\CumplimientoController;
use App\Http\Controllers\ReprogramacionController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\HistorialActividadController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\RespaldoController;


// ==================================================
// INICIO DE SESIÓN
// ==================================================

// Define la respuesta que entrega esta ruta al usuario.
// descripcion: define la respuesta que entrega esta ruta al usuario.
// entradas: solicitud que llega a la ruta
// salida: muestra la pantalla o respuesta asignada a la ruta
Route::get('/', function () {
    return redirect()->route('login');
});

// Agrupa las rutas que comparten permisos de acceso.
// descripcion: agrupa las rutas que comparten permisos de acceso.
// entradas: rutas que pertenecen al grupo
// salida: aplica a las rutas los permisos compartidos
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrarInicioSesion'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'iniciarSesion'])
        ->name('iniciarSesion');

    Route::get('/forgot-password', [PasswordResetController::class, 'solicitar'])
        ->name('password.request');

    Route::post('/forgot-password', [PasswordResetController::class, 'enviarEnlace'])
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'cambiar'])
        ->name('password.reset');

    Route::post('/reset-password', [PasswordResetController::class, 'guardarNueva'])
        ->name('password.update');
});

// Agrupa las rutas que comparten permisos de acceso.
// descripcion: agrupa las rutas que comparten permisos de acceso.
// entradas: rutas que pertenecen al grupo
// salida: aplica a las rutas los permisos compartidos
Route::middleware('auth')->group(function () {

Route::post('/logout', [LoginController::class, 'cerrarSesion'])
    ->name('cerrarSesion');

// Panel principal

// Define la respuesta que entrega esta ruta al usuario.
// descripcion: define la respuesta que entrega esta ruta al usuario.
// entradas: solicitud que llega a la ruta
// salida: muestra la pantalla o respuesta asignada a la ruta
Route::get('/dashboard', function () {
    app(\App\Services\RepeticionTandeoService::class)->crearSiguientes();

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
        // Define la respuesta que entrega esta ruta al usuario.
        // descripcion: define la respuesta que entrega esta ruta al usuario.
        // entradas: solicitud que llega a la ruta
        // salida: muestra la pantalla o respuesta asignada a la ruta
        ->when($busqueda !== '', function ($consulta) use ($busqueda) {
            // Define la respuesta que entrega esta ruta al usuario.
            // descripcion: define la respuesta que entrega esta ruta al usuario.
            // entradas: solicitud que llega a la ruta
            // salida: muestra la pantalla o respuesta asignada a la ruta
            $consulta->whereHas('circuito', function ($circuito) use ($busqueda) {
                $circuito->where('nombre', 'like', '%' . $busqueda . '%')
                    ->orWhere('descripcion', 'like', '%' . $busqueda . '%')
                    // Define la respuesta que entrega esta ruta al usuario.
                    // descripcion: define la respuesta que entrega esta ruta al usuario.
                    // entradas: solicitud que llega a la ruta
                    // salida: muestra la pantalla o respuesta asignada a la ruta
                    ->orWhereHas('zona', function ($zona) use ($busqueda) {
                        $zona->where('nombre', 'like', '%' . $busqueda . '%');
                    });
            });
        })
        // Define la respuesta que entrega esta ruta al usuario.
        // descripcion: define la respuesta que entrega esta ruta al usuario.
        // entradas: solicitud que llega a la ruta
        // salida: muestra la pantalla o respuesta asignada a la ruta
        ->when($busqueda === '', function ($consulta) use ($inicioMes, $finMes) {
            $consulta->whereBetween('fecha', [$inicioMes, $finMes]);
        })
        // Define la respuesta que entrega esta ruta al usuario.
        // descripcion: define la respuesta que entrega esta ruta al usuario.
        // entradas: solicitud que llega a la ruta
        // salida: muestra la pantalla o respuesta asignada a la ruta
        ->when(auth()->user()->rol === 'tandeador', function ($consulta) {
            // Define la respuesta que entrega esta ruta al usuario.
            // descripcion: define la respuesta que entrega esta ruta al usuario.
            // entradas: solicitud que llega a la ruta
            // salida: muestra la pantalla o respuesta asignada a la ruta
            $consulta->where(function ($filtro) {
                $filtro->where('tandeador_id', auth()->user()->getKey())
                    // Define la respuesta que entrega esta ruta al usuario.
                    // descripcion: define la respuesta que entrega esta ruta al usuario.
                    // entradas: solicitud que llega a la ruta
                    // salida: muestra la pantalla o respuesta asignada a la ruta
                    ->orWhereHas('tandeadores', function ($personas) {
                        $personas->where('usuarios.id', auth()->user()->getKey());
                    });
            });
        })
        ->with([
            'tandeadores',
            'circuito.zona',
            'circuito.puntoAbastecimiento',
            // Define la respuesta que entrega esta ruta al usuario.
            // descripcion: define la respuesta que entrega esta ruta al usuario.
            // entradas: solicitud que llega a la ruta
            // salida: muestra la pantalla o respuesta asignada a la ruta
            'cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            },
        ])
        ->orderBy('fecha')
        ->orderBy('hora_inicio_programada')
        ->get();

    return view('dashboard', compact('tandeosMes', 'mesActual', 'mesNumero', 'anioSeleccionado', 'fechaMes', 'busqueda'));
})->name('dashboard');

Route::get('/calendario', [CalendarioController::class, 'index'])
    ->middleware('rol:administrador,capturista,tandeador')
    ->name('calendario.index');



Route::get('/calendario/eventos', [CalendarioController::class, 'eventos'])
    ->middleware('rol:administrador,capturista,tandeador')
    ->name('calendario.eventos');

Route::post('/calendario/mover', [CalendarioController::class, 'mover'])
    ->middleware('rol:administrador,capturista')
    ->name('calendario.mover');
Route::middleware('rol:administrador')->prefix('usuarios')
// Define la respuesta que entrega esta ruta al usuario.
// descripcion: define la respuesta que entrega esta ruta al usuario.
// entradas: solicitud que llega a la ruta
// salida: muestra la pantalla o respuesta asignada a la ruta
->name('usuarios.')->group(function () {
    Route::get('/', [UsuarioController::class, 'index'])
    ->name('index');
    Route::get('/crear', [UsuarioController::class, 'create'])
    ->name('create');
    Route::post('/', [UsuarioController::class, 'store'])
    ->name('store');
    Route::get('/{id}/editar', [UsuarioController::class, 'edit'])
    ->name('edit');
    Route::put('/{id}', [UsuarioController::class, 'update'])
    ->name('update');
    Route::delete('/{id}', [UsuarioController::class, 'destroy'])
    ->name('destroy');
});


Route::middleware('rol:administrador')->prefix('historial')
// Define la respuesta que entrega esta ruta al usuario.
// descripcion: define la respuesta que entrega esta ruta al usuario.
// entradas: solicitud que llega a la ruta
// salida: muestra la pantalla o respuesta asignada a la ruta
->name('historial.')->group(function () {
    Route::get('/', [HistorialActividadController::class, 'index'])
    ->name('index');
    Route::post('/depurar', [HistorialActividadController::class, 'depurar'])
    ->name('depurar');
});

Route::middleware('rol:administrador')->prefix('respaldos')
    ->name('respaldos.')->group(function () {
        Route::get('/', [RespaldoController::class, 'index'])->name('index');
        Route::post('/crear', [RespaldoController::class, 'store'])->name('store');
        Route::post('/{respaldo}/restaurar', [RespaldoController::class, 'restore'])->name('restore');
    });
// ==================================================

// REPORTES
Route::middleware('rol:administrador,capturista')->prefix('reportes')
// Define la respuesta que entrega esta ruta al usuario.
// descripcion: define la respuesta que entrega esta ruta al usuario.
// entradas: solicitud que llega a la ruta
// salida: muestra la pantalla o respuesta asignada a la ruta
->name('reportes.')->group(function () {
    Route::get('/', [ReportesController::class, 'index'])
    ->name('index');
    Route::get('/cumplimiento-circuito/excel', [ReportesController::class, 'cumplimientoCircuitoExcel'])
    ->name('cumplimiento.circuito.excel');
    Route::get('/vista-previa/{tipo}', [ReportesController::class, 'vistaPrevia'])
    ->name('vista-previa');
    Route::get('/{tipo}/pdf', [ReportesController::class, 'generarPdf'])
    ->name('pdf');
});

// ZONAS
// ==================================================

// Agrupa las rutas que comparten permisos de acceso.
// descripcion: agrupa las rutas que comparten permisos de acceso.
// entradas: rutas que pertenecen al grupo
// salida: aplica a las rutas los permisos compartidos
Route::middleware('rol:administrador,capturista')->group(function () {
    Route::get('/zonas', [ZonasController::class, 'index'])
    ->name('zonas.index');
    Route::get('/zonas/crear', [ZonasController::class, 'create'])
    ->name('createZona');
    Route::post('/zonas/guardar', [ZonasController::class, 'store'])
    ->name('storeZona');
    Route::put('/zonas/{zona}', [ZonasController::class, 'update'])
    ->name('updateZona');
    Route::put('/zonas/{id}/desactivar', [ZonasController::class, 'desactivar'])
    ->name('desactivarZona');
    Route::get('/zonas/{id}/editar', [ZonasController::class, 'edit'])
    ->name('editZona');
    Route::delete('/zonas/{id}', [ZonasController::class, 'destroy'])->name('deleteZona');
});


// ==================================================
// CIRCUITOS
// ==================================================

// Agrupa las rutas que comparten permisos de acceso.
// descripcion: agrupa las rutas que comparten permisos de acceso.
// entradas: rutas que pertenecen al grupo
// salida: aplica a las rutas los permisos compartidos
Route::middleware('rol:administrador,capturista')->group(function () {
Route::get('/circuitos', [CircuitosController::class, 'index'])
    ->name('circuitos.index');

Route::get('/circuitos/crear', [CircuitosController::class, 'create'])
    ->name('createCircuito');

Route::post('/circuitos/guardar', [CircuitosController::class, 'store'])
    ->name('storeCircuito');

Route::put('/circuitos/{circuito}', [CircuitosController::class, 'update'])
    ->name('updateCircuito');

Route::get('/circuitos/{id}/editar', [CircuitosController::class, 'edit'])
    ->name('editCircuito');

Route::delete('/circuitos/{id}', [CircuitosController::class, 'destroy'])
    ->name('deleteCircuito');

Route::put('/circuitos/{id}/desactivar', [CircuitosController::class, 'desactivar'])
    ->name('desactivarCircuito');
});


// ==================================================
// TANDEOS PROGRAMADOS
// ==================================================

Route::get('/tandeos', [TandeosController::class, 'index'])
    ->name('tandeos.index');

// Agrupa las rutas que comparten permisos de acceso.
// descripcion: agrupa las rutas que comparten permisos de acceso.
// entradas: rutas que pertenecen al grupo
// salida: aplica a las rutas los permisos compartidos
Route::middleware('rol:administrador,capturista')->group(function () {
    Route::get('/tandeos/crear', [TandeosController::class, 'create'])
    ->name('createTandeo');
    Route::post('/tandeos/guardar', [TandeosController::class, 'store'])
    ->name('storeTandeo');
    Route::get('/tandeos/{id}/editar', [TandeosController::class, 'edit'])
    ->name('editTandeo');
    Route::delete('/tandeos/{id}', [TandeosController::class, 'destroy'])->name('deleteTandeo');
});
    

// ==================================================
// CUMPLIMIENTO DE TANDEOS
// ==================================================

// Agrupa las rutas que comparten permisos de acceso.
// descripcion: agrupa las rutas que comparten permisos de acceso.
// entradas: rutas que pertenecen al grupo
// salida: aplica a las rutas los permisos compartidos
Route::middleware('rol:administrador,capturista')->group(function () {
    Route::get('/cumplimiento', [CumplimientoController::class, 'index'])
    ->name('cumplimiento.index');
    Route::post('/cumplimiento/guardar', [CumplimientoController::class, 'store'])
    ->name('storeCumplimiento');
    Route::delete('/cumplimiento/{id}', [CumplimientoController::class, 'destroy'])
    ->name('deleteCumplimiento');
});

    
// ==================================================
// REPROGRAMACIONES
// ==================================================

// Agrupa las rutas que comparten permisos de acceso.
// descripcion: agrupa las rutas que comparten permisos de acceso.
// entradas: rutas que pertenecen al grupo
// salida: aplica a las rutas los permisos compartidos
Route::middleware('rol:administrador,capturista')->group(function () {
Route::post('/reprogramaciones/apagon/previa', [ReprogramacionController::class, 'previa'])
    ->name('reprogramaciones.apagon.previa');

Route::post('/reprogramaciones/apagon', [ReprogramacionController::class, 'store'])
    ->name('reprogramaciones.apagon.store');

Route::post('/reprogramaciones/fuga/previa', [ReprogramacionController::class, 'previaFuga'])
    ->name('reprogramaciones.fuga.previa');

Route::post('/reprogramaciones/fuga', [ReprogramacionController::class, 'storeFuga'])
    ->name('reprogramaciones.fuga.store');

Route::post('/reprogramaciones/manual/previa', [ReprogramacionController::class, 'previaManual'])
    ->name('reprogramaciones.manual.previa');

Route::post('/reprogramaciones/manual/guardar', [ReprogramacionController::class, 'storeManual'])
    ->name('reprogramaciones.manual.store');

});
});

