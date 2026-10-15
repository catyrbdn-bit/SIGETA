<?php

namespace App\Models;

use App\Traits\TracksSyncChanges;

use App\Traits\HasSyncUuid;

use Illuminate\Database\Eloquent\Model;

class PuntoAbastecimiento extends Model
{
    use TracksSyncChanges;
    use HasSyncUuid;
    
    protected $table = 'puntos_abastecimiento';

    protected $fillable = [
        'nombre', 
        'tipo', 
        'horas_atraso_apagon', 
        'activo'];

}
