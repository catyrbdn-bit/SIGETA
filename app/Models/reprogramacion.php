<?php

namespace App\Models;

// archivo del sistema: archivo que representa datos y relaciones de la base de datos

use Illuminate\Database\Eloquent\Model;

class Reprogramacion extends Model
{
    // Estas variables estan definidas como protegidas para que solo puedan ser accedidas desde la clase misma o sus subclases,
    // evitando modificaciones externas no deseadas
    protected $table = 'reprogramaciones';

    protected $fillable = [
        'tandeo_id', 'cadena_uuid', 'tipo_origen', 'es_manual', 'es_efecto_cadena',
        'punto_abastecimiento_id', 'zona_id',
        'inicio_anterior', 'fin_anterior', 'inicio_nuevo', 'fin_nuevo',
        'horas_atraso', 'usuario_id', 'observaciones',
    ];

    protected $casts = [
        'inicio_anterior' => 'datetime',
        'fin_anterior' => 'datetime',
        'inicio_nuevo' => 'datetime',
        'fin_nuevo' => 'datetime',
        'es_manual' => 'boolean',
        'es_efecto_cadena' => 'boolean',
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
}
