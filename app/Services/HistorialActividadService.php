<?php

namespace App\Services;
// archivo del sistema: archivo con procesos que utiliza el sistema

use App\Models\HistorialActividad;

class HistorialActividadService
{

    /**
     * Guarda una acción realizada por un usuario en el historial de actividad.
     *
     * @param string $tipo Tipo de actividad registrada.
     * @param string $accion Nombre de la acción realizada.
     * @param string $descripcion Resumen de lo ocurrido.
     * @param array $detalles Información adicional de la acción.
     * @param int|null $usuarioId Identificador del usuario, si se proporciona.
     * @param string|null $usuarioNombre Nombre de usuario, si se proporciona.
     * @return void
     */
    public function registrar(
        string $tipo,
        string $accion,
        string $descripcion,
        array $detalles = [],
        ?int $usuarioId = null,
        ?string $usuarioNombre = null
        //despues de los parametros opcionales, 
        // se puede agregar un parametro para el id del usuario y 
        // el nombre del usuario, si no se proporciona, se obtiene del usuario 
        // autenticado
    ): void {
        $usuario = auth()->user();

        //el if siguiente es para que si no se proporciona un id de usuario, 
        // se obtenga del usuario autenticado
        if ($usuarioId === null && $usuario) {
            $usuarioId = (int) $usuario->getKey();
        }

        if ($usuarioNombre === null && $usuario) {
            $usuarioNombre = $usuario->usuario ?: $usuario->nombre;
        }

        //finalmente, se crea un registro en la tabla historial_actividades 
        // con los datos proporcionados
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