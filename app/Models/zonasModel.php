<?php

namespace App\Models;
// archivo del sistema: archivo que representa datos y relaciones de la base de datos


use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class zonasModel extends Authenticatable
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

    // relaciona el registro con sus tandeadores asignados
    // Relaciona el tandeo con las personas asignadas para atenderlo.
    // descripcion: relaciona el tandeo con las personas asignadas para atenderlo.
    // entradas: registro actual del modelo
    // salida: permite consultar los tandeadores asignados
    public function tandeadores()
    {
        return $this->belongsToMany(usuarioModel::class, 'tandeador_zona', 'zona_id', 'usuario_id');
    }
}