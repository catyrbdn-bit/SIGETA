<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class usuarioModel extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'usuario',
        'email',
        'password',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];


 //Indica a Laravel qué campo utilizar
 //como identificador para iniciar sesión.
 
    public function getAuthIdentifierName()
    {
        return 'usuario';
    }

    public function zonas()
    {
        return $this->belongsToMany(zonasModel::class, 'tandeador_zona', 'usuario_id', 'zona_id');
    }
}
