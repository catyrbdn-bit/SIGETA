<?php

namespace App\Models;

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

    public function tandeo()
    {
        return $this->belongsTo(TandeoProgramado::class, 'tandeo_id');
    }

    public function capturadoPor()
    {
        return $this->belongsTo(usuarioModel::class, 'capturado_por_id');
    }
}
