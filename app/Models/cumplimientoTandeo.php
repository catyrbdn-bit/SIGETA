<?php

namespace App\Models;
// archivo del sistema: archivo que representa datos y relaciones de la base de datos



use Illuminate\Database\Eloquent\Model;

class cumplimientoTandeo extends Model
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
    public function tandeo()
    {
        return $this->belongsTo(TandeoProgramado::class, 'tandeo_id');
    }

    // relaciona el cumplimiento con el usuario que lo registro
    public function capturadoPor()
    {
        return $this->belongsTo(usuarioModel::class, 'capturado_por_id');
    }
}
