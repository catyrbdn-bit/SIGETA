<?php

namespace App\Http\Controllers;

use App\Models\HistorialActividad;
use App\Models\usuarioModel;
use App\Services\HistorialActividadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class historialActividadController extends controller
{
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
            ->when($filtros['usuario'] ?? null, fn ($consulta, $usuario) => $consulta->where('usuario_nombre', $usuario))
            ->when($filtros['tipo'] ?? null, fn ($consulta, $tipo) => $consulta->where('tipo', $tipo))
            ->when($filtros['desde'] ?? null, fn ($consulta, $fecha) => $consulta->whereDate('created_at', '>=', $fecha))
            ->when($filtros['hasta'] ?? null, fn ($consulta, $fecha) => $consulta->whereDate('created_at', '<=', $fecha))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $usuarios = HistorialActividad::whereNotNull('usuario_nombre')->distinct()->orderBy('usuario_nombre')->pluck('usuario_nombre');
        $tipos = HistorialActividad::select('tipo')->distinct()->orderBy('tipo')->pluck('tipo');

        return view('historial.index', compact('registros', 'usuarios', 'tipos', 'filtros'));
    }

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