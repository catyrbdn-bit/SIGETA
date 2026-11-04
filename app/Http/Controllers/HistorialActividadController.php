<?php

namespace App\Http\Controllers;
// archivo del sistema: archivo con acciones para las pantallas del sistema

use App\Models\HistorialActividad;
use App\Models\UsuarioModel;
use App\Services\HistorialActividadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HistorialActividadController extends Controller
{
    // consulta y muestra los registros de la pantalla
    // Consulta los registros y prepara la pantalla de listado.
    // descripcion: consulta los registros y prepara la pantalla de listado.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: muestra la pantalla con los datos consultados
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'usuario' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'tipo' => ['nullable', 'string', 'max:60'],
        ], [
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ]);

        $registros = HistorialActividad::query()
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when($filtros['usuario'] ?? null, fn ($consulta, $usuario) => $consulta->where('usuario_nombre', $usuario))
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when($filtros['tipo'] ?? null, fn ($consulta, $tipo) => $consulta->where('tipo', $tipo))
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when($filtros['desde'] ?? null, fn ($consulta, $fecha) => $consulta->whereDate('created_at', '>=', $fecha))
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when($filtros['hasta'] ?? null, fn ($consulta, $fecha) => $consulta->whereDate('created_at', '<=', $fecha))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $usuarios = HistorialActividad::whereNotNull('usuario_nombre')->distinct()->orderBy('usuario_nombre')->pluck('usuario_nombre');
        $tipos = HistorialActividad::select('tipo')->distinct()->orderBy('tipo')->pluck('tipo');

        return view('historial.index', compact('registros', 'usuarios', 'tipos', 'filtros'));
    }

    // elimina los registros antiguos despues de validar la contrasena
    // Elimina los registros antiguos después de confirmar la contraseña.
    // descripcion: elimina los registros antiguos después de confirmar la contraseña.
    // entradas: solicitud con los datos enviados por el usuario; dato necesario para realizar el proceso
    // salida: elimina los registros permitidos y deja constancia en el historial
    public function depurar(Request $request, HistorialActividadService $historial)
    {
        $datos = $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Escribe tu contraseña para confirmar la depuración.',
        ]);

        if (! Hash::check($datos['password'], auth()->user()->password)) {
            return back()->withErrors([
                'password' => 'La contraseña no es correcta. No se eliminó ningún registro.',
            ]);
        }

        // Agrupa los cambios para guardarlos como una sola operación.
        // descripcion: agrupa los cambios para guardarlos como una sola operación.
        // entradas: registros que se actualizan durante la operación
        // salida: confirma los cambios o los revierte si ocurre un error
        $cantidad = DB::transaction(function () use ($historial) {
            $fechaCorte = now()->subYear();
            $cantidad = HistorialActividad::where('created_at', '<', $fechaCorte)->count();

            HistorialActividad::where('created_at', '<', $fechaCorte)->delete();

            $historial->registrar(
                'depuracion',
                'eliminacion',
                'Depuración manual del historial de actividad.',
                [
                    'registros_eliminados' => $cantidad,
                    'fecha_corte' => $fechaCorte->format('Y-m-d H:i:s'),
                ]
            );

            return $cantidad;
        });

        return redirect()->route('historial.index')->with(
            'success',
            $cantidad > 0
                ? "Se eliminaron {$cantidad} registros con más de un año de antigüedad. La depuración quedó registrada."
                : 'No había registros con más de un año de antigüedad. La revisión quedó registrada.'
        );
    }
}