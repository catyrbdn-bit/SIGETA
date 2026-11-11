<?php

namespace App\Models;

// archivo del sistema: archivo que representa datos y relaciones de la base de datos

use Illuminate\Database\Eloquent\Model;

class HistorialActividad extends Model
{
    protected $table = 'historial_actividad';

    protected $fillable = [
        'usuario_id',
        'usuario_nombre',
        'tipo',
        'accion',
        'descripcion',
        'detalles',
        'ip',
        'respaldo_id',
    ];

    protected $casts = [
        'detalles' => 'array',
    ];

    // relaciona el historial con el usuario que realizo la accion
    // Relaciona el historial con el usuario que realizó la acción.
    // descripcion: relaciona el historial con el usuario que realizó la acción.
    // entradas: registro actual del modelo
    // salida: permite consultar los datos del usuario relacionado
    public function usuario()
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_id');
    }
}
