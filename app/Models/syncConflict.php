<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Model;

class syncConflict extends Model
{
    use HasSyncUuid;

    protected $table = 'sync_conflicts';

    protected $fillable = [
        'sync_uuid', 'sync_change_id', 'datos_locales', 'datos_centrales',
        'motivo', 'estado', 'resuelto_por_id', 'resolucion', 'resuelto_en',
    ];

    protected $casts = [
        'datos_locales' => 'array',
        'datos_centrales' => 'array',
        'resolucion' => 'array',
        'resuelto_en' => 'datetime',
    ];

    public function cambio()
    {
        return $this->belongsTo(syncChange::class, 'sync_change_id');
    }

    public function resueltoPor()
    {
        return $this->belongsTo(usuarioModel::class, 'resuelto_por_id');
    }
}