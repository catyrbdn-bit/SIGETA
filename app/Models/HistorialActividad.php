<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'detalles' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(usuarioModel::class, 'usuario_id');
    }
}