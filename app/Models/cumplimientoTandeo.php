<?php

namespace App\Models;

// archivo del sistema: archivo que representa datos y relaciones de la base de datos

use Illuminate\Database\Eloquent\Model;

class CumplimientoTandeo extends Model
{
    protected $table = 'cumplimiento_tandeos';

    protected $fillable = [
        'tandeo_id',
        'resultado',
        'horas_atraso',
        'motivo',
        'observaciones',
        'capturado_por_id',
        'fecha_captura',
    ];

    // relaciona el registro con su tandeo programado
    // Relaciona el registro con el tandeo programado al que pertenece.
    // descripcion: relaciona el registro con el tandeo programado al que pertenece.
    // entradas: no requiere datos externos
    // salida: permite consultar el tandeo relacionado
    public function tandeo()
    {
        return $this->belongsTo(TandeoProgramado::class, 'tandeo_id');
    }

    // relaciona el cumplimiento con el usuario que lo registro
    // Relaciona el cumplimiento con el usuario que lo registró.
    // descripcion: relaciona el cumplimiento con el usuario que lo registró.
    // entradas: registro actual del modelo
    // salida: permite consultar quién capturó el cumplimiento
    public function capturadoPor()
    {
        return $this->belongsTo(UsuarioModel::class, 'capturado_por_id');
    }
}
