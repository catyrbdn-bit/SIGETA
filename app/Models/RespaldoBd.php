<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespaldoBd extends Model
{
    protected $table = 'respaldos_bd';

    protected $fillable = [
        'usuario_id',
        'nombre_archivo',
        'ruta_archivo',
        'tamano_bytes',
        'estado',
    ];

    public function usuario()
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_id');
    }
}
