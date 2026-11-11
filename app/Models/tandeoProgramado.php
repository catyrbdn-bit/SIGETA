<?php

namespace App\Models;

// archivo del sistema: archivo que representa datos y relaciones de la base de datos

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TandeoProgramado extends Model
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
        'repeticion_uuid',
        'repetir_cada_dias',
        'fecha_repeticion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // realiza el proceso de apoyo llamado circuito
    // Relaciona el tandeo con el circuito al que pertenece.
    // descripcion: relaciona el tandeo con el circuito al que pertenece.
    // entradas: registro actual del modelo
    // salida: permite consultar los datos del circuito
    public function circuito()
    {
        return $this->belongsTo(CircuitosModel::class, 'circuito_id');
    }

    // relaciona el tandeo con su tandeador principal
    // Relaciona el tandeo con su tandeador principal.
    // descripcion: relaciona el tandeo con su tandeador principal.
    // entradas: registro actual del modelo
    // salida: permite consultar al tandeador asignado
    public function tandeador()
    {
        return $this->belongsTo(UsuarioModel::class, 'tandeador_id');
    }

    // relaciona el registro con sus tandeadores asignados
    // Relaciona el tandeo con las personas asignadas para atenderlo.
    // descripcion: relaciona el tandeo con las personas asignadas para atenderlo.
    // entradas: registro actual del modelo
    // salida: permite consultar los tandeadores asignados
    public function tandeadores()
    {
        return $this->belongsToMany(UsuarioModel::class, 'tandeo_tandeador', 'tandeo_id', 'usuario_id');
    }

    // relaciona el tandeo con sus registros de cumplimiento
    // Relaciona el tandeo con sus registros de cumplimiento.
    // descripcion: relaciona el tandeo con sus registros de cumplimiento.
    // entradas: registro actual del modelo
    // salida: permite consultar los cumplimientos del tandeo
    public function cumplimientos()
    {
        return $this->hasMany(CumplimientoTandeo::class, 'tandeo_id');
    }

    // Fecha y hora de inicio completas (calculada)
    // Calcula la fecha y hora de inicio del tandeo.
    // descripcion: calcula la fecha y hora de inicio del tandeo.
    // entradas: no requiere datos externos
    // salida: devuelve la fecha y hora de inicio
    public function getInicioAttribute(): Carbon
    {
        return Carbon::parse($this->fecha->format('Y-m-d').' '.$this->hora_inicio_programada);
    }

    // Fecha y hora de fin completas; si termina "antes" de empezar, cruzó la medianoche
    // Calcula la fecha y hora de término del tandeo.
    // descripcion: calcula la fecha y hora de término del tandeo.
    // entradas: no requiere datos externos
    // salida: devuelve la fecha y hora de término, considerando el cambio de día
    public function getFinAttribute(): Carbon
    {
        $fin = Carbon::parse($this->fecha->format('Y-m-d').' '.$this->hora_fin_programada);

        return $fin <= $this->inicio ? $fin->addDay() : $fin;
    }

    // Guarda un nuevo horario a partir de fechas y horas completas
    // Actualiza la fecha, hora de inicio y hora de término del tandeo.
    // descripcion: actualiza la fecha, hora de inicio y hora de término del tandeo.
    // entradas: fecha u horario que se debe revisar o cambiar
    // salida: guarda el nuevo horario en el registro
    public function aplicarHorario(Carbon $inicio, Carbon $fin): void
    {
        $this->update([
            'fecha' => $inicio->toDateString(),
            'hora_inicio_programada' => $inicio->format('H:i:s'),
            'hora_fin_programada' => $fin->format('H:i:s'),
        ]);
    }
}
