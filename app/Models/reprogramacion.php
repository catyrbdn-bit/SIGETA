<?php

namespace App\Models;

use App\Traits\TracksSyncChanges;

use App\Traits\HasSyncUuid;

use Illuminate\Database\Eloquent\Model;

class Reprogramacion extends Model
{

//estos use permiten que el modelo tenga un UUID único para sincronización 
// y que registre cambios para sincronización
    use TracksSyncChanges;
    use HasSyncUuid;


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
