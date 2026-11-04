<?php

namespace App\Models;
// archivo del sistema: archivo que representa datos y relaciones de la base de datos


use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ZonasModel extends Authenticatable
{
    use Notifiable;

    protected $table = 'zonas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $hidden = [
        // para campos ocultos, si los hubiera
    ];

    // Relaciona la colonia con sus circuitos.
    public function circuitos()
    {
        return $this->hasMany(CircuitosModel::class, 'zona_id');
    }

    // Relaciona la colonia con los tandeos de sus circuitos.
    public function tandeos()
    {
        return $this->hasManyThrough(
            TandeoProgramado::class,
            CircuitosModel::class,
            'zona_id',
            'circuito_id'
        );
    }

    // relaciona el registro con sus tandeadores asignados
    // Relaciona el tandeo con las personas asignadas para atenderlo.
    // descripcion: relaciona el tandeo con las personas asignadas para atenderlo.
    // entradas: registro actual del modelo
    // salida: permite consultar los tandeadores asignados
    public function tandeadores()
    {
        return $this->belongsToMany(UsuarioModel::class, 'tandeador_zona', 'zona_id', 'usuario_id');
    }
}