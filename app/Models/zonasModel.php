<?php

namespace App\Models;

use App\Traits\TracksSyncChanges;

use App\Traits\HasSyncUuid;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class zonasModel extends Authenticatable
{
    use TracksSyncChanges;
    use HasSyncUuid;
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


}