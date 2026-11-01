<?php

namespace App\Services;
// archivo del sistema: archivo con procesos que utiliza el sistema

use App\Models\HistorialActividad;

class HistorialActividadService
{
    //Esta función registra una actividad en el historial de actividades, 
    // incluyendo información sobre el usuario, tipo de actividad, acción realizada, 
    // descripción y detalles adicionales. Si no se proporciona un ID o nombre de usuario,
    // Guarda una acción realizada para que aparezca en el historial.
    // descripcion: guarda una acción realizada para que aparezca en el historial.
    // entradas: dato necesario para realizar el proceso; lista de datos que se deben procesar; identificador del registro relacionado
    // salida: crea un registro de actividad con sus detalles
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