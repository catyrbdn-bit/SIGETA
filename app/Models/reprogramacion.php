<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reprogramacion extends Model
{
    protected $table = 'reprogramaciones';

    protected $fillable = [
        'tandeo_id', 'cadena_uuid', 'tipo_origen', 'es_manual', 'es_efecto_cadena',
        'punto_abastecimiento_id', 'zona_id',
        'inicio_anterior', 'fin_anterior', 'inicio_nuevo', 'fin_nuevo',
        'horas_atraso', 'usuario_id', 'observaciones',
    ];

    protected $casts = [
        'inicio_anterior' => 'datetime',
        'fin_anterior'    => 'datetime',
        'inicio_nuevo'    => 'datetime',
        'fin_nuevo'       => 'datetime',
        'es_manual'       => 'boolean',
        'es_efecto_cadena' => 'boolean',
    ];
}
