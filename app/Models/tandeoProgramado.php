<?php

namespace App\Models;
// archivo del sistema: archivo que representa datos y relaciones de la base de datos



use Carbon\Carbon;
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
        'repeticion_uuid',
        'repetir_cada_dias',
        'fecha_repeticion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // realiza el proceso de apoyo llamado circuito
    public function circuito()
    {
        return $this->belongsTo(circuitosModel::class, 'circuito_id');
    }

    // relaciona el tandeo con su tandeador principal
    public function tandeador()
    {
        return $this->belongsTo(usuarioModel::class, 'tandeador_id');
    }

    // relaciona el registro con sus tandeadores asignados
    public function tandeadores()
    {
        return $this->belongsToMany(usuarioModel::class, 'tandeo_tandeador', 'tandeo_id', 'usuario_id');
    }


    // relaciona el tandeo con sus registros de cumplimiento
    public function cumplimientos()
    {
        return $this->hasMany(CumplimientoTandeo::class, 'tandeo_id');
    }

    // Fecha y hora de inicio completas (calculada)
    public function getInicioAttribute(): Carbon
    {
        return Carbon::parse($this->fecha->format('Y-m-d') . ' ' . $this->hora_inicio_programada);
    }

    // Fecha y hora de fin completas; si termina "antes" de empezar, cruzó la medianoche
    public function getFinAttribute(): Carbon
    {
        $fin = Carbon::parse($this->fecha->format('Y-m-d') . ' ' . $this->hora_fin_programada);

        return $fin <= $this->inicio ? $fin->addDay() : $fin;
    }

    // Guarda un nuevo horario a partir de fechas y horas completas
    public function aplicarHorario(Carbon $inicio, Carbon $fin): void
    {
        $this->update([
            'fecha'                  => $inicio->toDateString(),
            'hora_inicio_programada' => $inicio->format('H:i:s'),
            'hora_fin_programada'    => $fin->format('H:i:s'),
        ]);
    }
}
