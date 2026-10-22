<?php

namespace App\Services;

use App\Models\HistorialActividad;

class HistorialActividadService
{
    public function registrar(
        string $tipo,
        string $accion,
        string $descripcion,
        array $detalles = [],
        ?int $usuarioId = null,
        ?string $usuarioNombre = null
    ): void {
        $usuario = auth()->user();

        if ($usuarioId === null && $usuario) {
            $usuarioId = (int) $usuario->getKey();
        }

        if ($usuarioNombre === null && $usuario) {
            $usuarioNombre = $usuario->usuario ?: $usuario->nombre;
        }

        HistorialActividad::create([
            'usuario_id' => $usuarioId,
            'usuario_nombre' => $usuarioNombre,
            'tipo' => $tipo,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'detalles' => $detalles ?: null,
            'ip' => request()->ip(),
        ]);
    }
}