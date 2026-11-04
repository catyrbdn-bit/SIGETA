<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'tandeo_id',
        'destinatario_email',
        'tipo',
        'asunto',
        'mensaje',
        'estado',
        'intentos',
        'ultimo_error',
        'reintentar_en',
        'enviado_at',
    ];

    protected $casts = [
        'reintentar_en' => 'datetime',
        'enviado_at' => 'datetime',
    ];
}
