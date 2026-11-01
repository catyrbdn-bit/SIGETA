<?php

namespace App\Models;
// archivo del sistema: archivo que representa datos y relaciones de la base de datos

use Illuminate\Database\Eloquent\Model;

class TandeoRepeticion extends Model
{

    protected $table = 'tandeo_repeticiones';

    protected $fillable = [
        'repeticion_uuid',
        'circuito_id',
        'fecha_ancla',
        'hora_inicio',
        'duracion_minutos',
        'tandeador_id',
        'cada_dias',
        'activa',
    ];

    protected $casts = [
        'fecha_ancla' => 'date',
        'activa' => 'boolean',
    ];

    // realiza el proceso de apoyo llamado circuito
    public function circuito()
    {
        return $this->belongsTo(circuitosModel::class, 'circuito_id');
    }
}
