<?php

namespace App\Models;

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

    public function circuito()
    {
        return $this->belongsTo(circuitosModel::class, 'circuito_id');
    }
}
