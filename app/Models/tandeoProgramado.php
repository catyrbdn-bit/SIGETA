<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tandeoProgramado extends Model
{
    
    protected $table = 'tandeos_programados';

    protected $fillable = [
        'circuito_id',
        'fecha',
        'hora_inicio_programada',
        'hora_fin_programada',
        'hora_inicio_real',
        'hora_fin_real',
        'estado',
        'tandeador_id',
    ];

    public function circuito()
    {
        return $this->belongsTo(circuitosModel::class, 'circuito_id');
    }

    public function tandeador()
    {
        return $this->belongsTo(usuarioModel::class, 'tandeador_id');
    }

    public function cumplimientos()
    {
        return $this->hasMany(CumplimientoTandeo::class, 'tandeo_id');
    }
}
